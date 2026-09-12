<?php

namespace JobMarket\Domain;

use JobMarket\Exceptions\AppException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Infrastructure\JobReportRepository;
use JobMarket\Support\Pagination;

class JobReportService
{
    private const REASONS = ["scam", "salary_mismatch", "fee_required", "inappropriate", "other"];
    private JobReportRepository $reports;
    private JobRepository $jobs;
    private NotificationService $notifications;
    private AdminService $adminService;

    public function __construct()
    {
        $this->reports = new JobReportRepository();
        $this->jobs = new JobRepository();
        $this->notifications = new NotificationService();
        $this->adminService = new AdminService();
    }

    public function report(array $user, string $jobId, array $data): array
    {
        if (!in_array($user["role"] ?? "", ["student", "developer"], true)) {
            throw new AuthorizationException("Chỉ sinh viên mới có thể báo cáo tin tuyển dụng.");
        }
        $job = $this->jobs->findById($jobId);
        if (!$job || !empty($job["deleted_at"])) {
            throw new NotFoundException("Không tìm thấy tin tuyển dụng.");
        }
        if ($this->reports->findExisting($user["id"], $jobId)) {
            throw new AppException("Bạn đã báo cáo tin tuyển dụng này. Quản trị viên đang tiếp nhận.", Response::HTTP_CONFLICT);
        }
        $reason = is_scalar($data["reason"] ?? null) ? trim((string)$data["reason"]) : "";
        if (!in_array($reason, self::REASONS, true)) {
            throw new ValidationException(["reason" => ["Vui lòng chọn lý do báo cáo hợp lệ."]]);
        }
        $description = is_scalar($data["description"] ?? null) ? trim((string)$data["description"]) : "";
        if (mb_strlen($description) > 500) {
            throw new ValidationException(["description" => ["Mô tả không được vượt quá 500 ký tự."]]);
        }
        if ($reason === "other" && $description === "") {
            throw new ValidationException(["description" => ["Vui lòng mô tả rõ vấn đề khi chọn lý do khác."]]);
        }
        return $this->reports->create([
            "id" => "jrep-" . bin2hex(random_bytes(12)),
            "user_id" => $user["id"],
            "job_id" => $jobId,
            "reason" => $reason,
            "description" => $description !== "" ? $description : null,
        ]);
    }

    public function list(array $admin, array $filters, ?Pagination $pagination): array
    {
        $this->requireAdmin($admin);
        return [
            "items" => $this->reports->getAll($filters, $pagination),
            "total" => $this->reports->countAll($filters),
        ];
    }

    public function resolve(array $admin, string $id, array $data): array
    {
        $this->requireAdmin($admin);
        $report = $this->reports->findById($id);
        if (!$report) {
            throw new NotFoundException("Không tìm thấy báo cáo tin tuyển dụng.");
        }
        $status = is_scalar($data["status"] ?? null) ? trim((string)$data["status"]) : "";
        $action = is_scalar($data["resolution_action"] ?? null) ? trim((string)$data["resolution_action"]) : "none";
        $note = is_scalar($data["admin_note"] ?? null) ? trim((string)$data["admin_note"]) : "";
        if (!in_array($status, ["reviewing", "resolved", "dismissed"], true)) {
            throw new ValidationException(["status" => ["Trạng thái xử lý không hợp lệ."]]);
        }
        if (!in_array($action, ["none", "hide_job", "close_job"], true)) {
            throw new ValidationException(["resolution_action" => ["Hành động xử lý không hợp lệ."]]);
        }
        if (mb_strlen($note) > 1000) {
            throw new ValidationException(["admin_note" => ["Ghi chú không được vượt quá 1000 ký tự."]]);
        }
        if ($action !== "none" && $status !== "resolved") {
            throw new ValidationException(["resolution_action" => ["Chỉ được ẩn/đóng tin khi kết luận báo cáo là đã xử lý."]]);
        }
        if ($action !== "none") {
            $this->adminService->moderateJob($admin, $report["job_id"], [
                "status" => $action === "hide_job" ? "hidden" : "closed",
                "rejection_reason" => $note ?: "Xử lý theo báo cáo của người dùng.",
            ]);
        }
        $this->reports->updateResolution($id, $status, $action, $note !== "" ? $note : null, $admin["id"]);
        if (in_array($status, ["resolved", "dismissed"], true)) {
            try {
                $message = $status === "resolved"
                    ? "Báo cáo của bạn về tin '{$report['job_title']}' đã được quản trị viên xử lý."
                    : "Báo cáo của bạn về tin '{$report['job_title']}' đã được xem xét và không ghi nhận vi phạm.";
                $this->notifications->notify($report["user_id"], "Kết quả báo cáo tin", $message, "job_report_result", [
                    "job_id" => $report["job_id"], "report_id" => $id, "status" => $status,
                    "url" => "/viec-lam/" . $report["job_id"],
                ]);
            } catch (\Throwable) {
            }
        }
        return $this->reports->findById($id);
    }

    private function requireAdmin(array $user): void
    {
        if (($user["role"] ?? "") !== "admin") {
            throw new AuthorizationException("Chỉ quản trị viên mới có quyền xử lý báo cáo.");
        }
    }
}
