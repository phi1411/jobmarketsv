<?php

namespace JobMarket\Domain;

use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Infrastructure\AdminRepository;
use JobMarket\Support\Pagination;

class AdminService
{
    private AdminRepository $adminRepo;
    private NotificationService $notificationService;

    public function __construct(?AdminRepository $adminRepo = null, ?NotificationService $notificationService = null)
    {
        $this->adminRepo = $adminRepo ?? new AdminRepository();
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    private function requireAdminRole(array $user): void
    {
        $role = $user["role"] ?? "";
        if ($role !== "admin") {
            throw new AuthorizationException("Chỉ Quản trị viên (Admin) mới có quyền truy cập khu vực này.");
        }
    }

    // --- USERS MANAGEMENT ---

    public function listUsers(array $adminUser, array $filters = [], ?Pagination $pagination = null): array
    {
        $this->requireAdminRole($adminUser);

        $items = $this->adminRepo->getUsers($filters, $pagination);
        $total = $this->adminRepo->countUsers($filters);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    public function getUserDetail(array $adminUser, string $id): array
    {
        $this->requireAdminRole($adminUser);

        $user = $this->adminRepo->getUserById($id);
        if (!$user) {
            throw new NotFoundException("Không tìm thấy người dùng.");
        }

        return $user;
    }

    public function updateUserStatus(array $adminUser, string $id, array $data): array
    {
        $this->requireAdminRole($adminUser);

        $status = $data["status"] ?? "";
        $allowedStatuses = ["active", "suspended", "banned"];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new ValidationException(["status" => ["Trạng thái người dùng không hợp lệ. Cho phép: " . implode(", ", $allowedStatuses)]]);
        }

        $targetUser = $this->adminRepo->getUserById($id);
        if (!$targetUser) {
            throw new NotFoundException("Không tìm thấy người dùng.");
        }

        // Rule 6: Protect last active admin from being deactivated
        if ($targetUser["role"] === "admin" && $status !== "active") {
            $activeCount = $this->adminRepo->countActiveAdmins();
            if ($activeCount <= 1) {
                throw new ValidationException(["status" => ["Không thể vô hiệu hóa hoặc khóa tài khoản Quản trị viên (Admin) đang hoạt động duy nhất."]]);
            }
        }

        $this->adminRepo->updateUserStatus($id, $status);

        // Audit Log
        $this->adminRepo->logAudit(
            $adminUser["id"],
            "update_user_status",
            "user",
            $id,
            ["old_status" => $targetUser["status"], "new_status" => $status]
        );

        return $this->adminRepo->getUserById($id);
    }

    // --- COMPANIES MANAGEMENT ---

    public function listCompanies(array $adminUser, array $filters = [], ?Pagination $pagination = null): array
    {
        $this->requireAdminRole($adminUser);

        $items = $this->adminRepo->getCompanies($filters, $pagination);
        $total = $this->adminRepo->countCompanies($filters);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    public function verifyCompany(array $adminUser, string $id, array $data): array
    {
        $this->requireAdminRole($adminUser);

        $verificationStatus = $data["verification_status"] ?? "";
        $allowed = ["verified", "rejected", "pending"];
        if (!in_array($verificationStatus, $allowed, true)) {
            throw new ValidationException(["verification_status" => ["Trạng thái xác thực không hợp lệ. Cho phép: " . implode(", ", $allowed)]]);
        }

        $rejectionReason = isset($data["rejection_reason"]) ? trim((string)$data["rejection_reason"]) : null;

        // Rule 2: Rejection reason is mandatory when rejected
        if ($verificationStatus === "rejected" && empty($rejectionReason)) {
            throw new ValidationException(["rejection_reason" => ["Lý do từ chối là bắt buộc khi từ chối xác thực công ty."]]);
        }

        $company = $this->adminRepo->getCompanyById($id);
        if (!$company) {
            throw new NotFoundException("Không tìm thấy thông tin công ty.");
        }

        $this->adminRepo->updateCompanyVerification($id, $verificationStatus, $rejectionReason);

        // In-App Notification to company owner
        try {
            $ownerUserId = $company["user_id"] ?? null;
            if ($ownerUserId) {
                $title = match ($verificationStatus) {
                    "verified" => "Xác thực công ty thành công",
                    "rejected" => "Xác thực công ty bị từ chối",
                    default    => "Cập nhật trạng thái xác thực"
                };
                $msg = match ($verificationStatus) {
                    "verified" => "Hồ sơ công ty '{$company['name']}' của bạn đã được quản trị viên phê duyệt xác thực.",
                    "rejected" => "Hồ sơ công ty '{$company['name']}' bị từ chối xác thực. Lý do: {$rejectionReason}",
                    default    => "Hồ sơ công ty '{$company['name']}' đang được xem xét lại."
                };

                $this->notificationService->notify(
                    $ownerUserId,
                    $title,
                    $msg,
                    "company_verification",
                    [
                        "company_id"          => $id,
                        "verification_status" => $verificationStatus,
                        "rejection_reason"    => $rejectionReason
                    ]
                );
            }
        } catch (\Throwable) {
        }

        // Audit Log
        $this->adminRepo->logAudit(
            $adminUser["id"],
            "verify_company",
            "company",
            $id,
            ["verification_status" => $verificationStatus, "rejection_reason" => $rejectionReason]
        );

        return $this->adminRepo->getCompanyById($id);
    }

    // --- JOBS MODERATION ---

    public function listJobs(array $adminUser, array $filters = [], ?Pagination $pagination = null): array
    {
        $this->requireAdminRole($adminUser);

        $items = $this->adminRepo->getJobs($filters, $pagination);
        $total = $this->adminRepo->countJobs($filters);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    public function moderateJob(array $adminUser, string $id, array $data): array
    {
        $this->requireAdminRole($adminUser);

        $status = $data["status"] ?? "";
        $allowedStatuses = ["draft", "pending_approval", "published", "rejected", "hidden", "closed", "expired"];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new ValidationException(["status" => ["Trạng thái kiểm duyệt việc làm không hợp lệ. Cho phép: " . implode(", ", $allowedStatuses)]]);
        }

        $rejectionReason = isset($data["rejection_reason"]) ? trim((string)$data["rejection_reason"]) : null;

        // Rule 2: Rejection reason is mandatory when rejected
        if ($status === "rejected" && empty($rejectionReason)) {
            throw new ValidationException(["rejection_reason" => ["Lý do từ chối là bắt buộc khi từ chối tin tuyển dụng."]]);
        }

        $job = $this->adminRepo->getJobById($id);
        if (!$job) {
            throw new NotFoundException("Không tìm thấy tin tuyển dụng.");
        }

        $this->adminRepo->updateJobModeration($id, $status, $rejectionReason);

        // In-App Notification to company owner
        try {
            $companyUserId = $job["company_user_id"] ?? null;
            if ($companyUserId) {
                $title = match ($status) {
                    "published" => "Tin tuyển dụng đã được duyệt",
                    "rejected"  => "Tin tuyển dụng bị từ chối",
                    "hidden"    => "Tin tuyển dụng bị tạm ẩn",
                    default     => "Cập nhật trạng thái tin tuyển dụng"
                };
                $msg = match ($status) {
                    "published" => "Tin tuyển dụng '{$job['title']}' đã được quản trị viên duyệt công khai.",
                    "rejected"  => "Tin tuyển dụng '{$job['title']}' đã bị từ chối. Lý do: {$rejectionReason}",
                    "hidden"    => "Tin tuyển dụng '{$job['title']}' đã bị tạm ẩn khỏi danh sách công khai.",
                    default     => "Tin tuyển dụng '{$job['title']}' đã chuyển sang trạng thái: '{$status}'."
                };

                $this->notificationService->notify(
                    $companyUserId,
                    $title,
                    $msg,
                    "job_moderation",
                    [
                        "job_id"           => $id,
                        "status"           => $status,
                        "rejection_reason" => $rejectionReason
                    ]
                );
            }
        } catch (\Throwable) {
        }

        // Audit Log
        $this->adminRepo->logAudit(
            $adminUser["id"],
            "moderate_job",
            "job",
            $id,
            ["status" => $status, "rejection_reason" => $rejectionReason]
        );

        return $this->adminRepo->getJobById($id);
    }

    public function listAuditLogs(array $adminUser, array $filters = [], ?Pagination $pagination = null): array
    {
        $this->requireAdminRole($adminUser);

        $items = $this->adminRepo->getAuditLogs($filters, $pagination);
        $total = $this->adminRepo->countAuditLogs($filters);

        return [
            "items" => $items,
            "total" => $total
        ];
    }
}
