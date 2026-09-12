<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Application\Application;
use JobMarket\Domain\Application\ApplicationRepositoryInterface;
use JobMarket\Domain\Cv\CvStorageService;
use JobMarket\Exceptions\AppException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\ApplicationRepository;
use JobMarket\Infrastructure\ApplicationDecisionDeliveryRepository;
use JobMarket\Infrastructure\CompanyRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Infrastructure\ProfileRepository;
use JobMarket\Support\Pagination;

class ApplicationService
{
    public const AI_MATCH_NOTICE_VERSION = 'ai-match.v1';

    private ApplicationRepository $applicationRepo;
    private JobRepository $jobRepo;
    private CompanyRepository $companyRepo;
    private ProfileRepository $profileRepo;
    private NotificationService $notificationService;
    private CvStorageService $cvStorageService;
    private MailService $mailService;
    private ApplicationDecisionDeliveryRepository $decisionDeliveryRepo;

    public function __construct(
        ?ApplicationRepository $applicationRepo = null,
        ?JobRepository $jobRepo = null,
        ?CompanyRepository $companyRepo = null,
        ?ProfileRepository $profileRepo = null,
        ?NotificationService $notificationService = null,
        ?CvStorageService $cvStorageService = null,
        ?MailService $mailService = null,
        ?ApplicationDecisionDeliveryRepository $decisionDeliveryRepo = null
    ) {
        $this->applicationRepo = $applicationRepo ?? new ApplicationRepository();
        $this->jobRepo = $jobRepo ?? new JobRepository();
        $this->companyRepo = $companyRepo ?? new CompanyRepository();
        $this->profileRepo = $profileRepo ?? new ProfileRepository();
        $this->notificationService = $notificationService ?? new NotificationService();
        $this->cvStorageService = $cvStorageService ?? new CvStorageService();
        $this->mailService = $mailService ?? new MailService();
        $this->decisionDeliveryRepo = $decisionDeliveryRepo ?? new ApplicationDecisionDeliveryRepository();
    }

    /**
     * Student submits application for a job
     */
    public function apply(array $user, string $jobId, array $data): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền nộp đơn ứng tuyển.");
        }

        $studentUserId = $user["id"] ?? "";

        // 1. Verify Job existence & eligibility
        $job = $this->jobRepo->findById($jobId);
        if (!$job || $job["status"] === "closed" || !empty($job["deleted_at"])) {
            throw new ValidationException(["job_id" => ["Tin tuyển dụng không tồn tại hoặc đã đóng."]]);
        }
        if ($job["status"] !== "published") {
            throw new ValidationException(["job_id" => ["Tin tuyển dụng chưa được công khai nên không thể nộp đơn."]]);
        }
        if ($job["status"] === "expired" || (!empty($job["application_deadline"]) && strtotime($job["application_deadline"]) < strtotime(date("Y-m-d")))) {
            throw new ValidationException(["job_id" => ["Hạn nộp hồ sơ cho công việc này đã kết thúc."]]);
        }

        // 2. Check for duplicate application (One-time application rule)
        $existing = $this->applicationRepo->findByJobAndStudent($jobId, $studentUserId);
        if ($existing) {
            throw new AppException("Bạn đã ứng tuyển vào vị trí này rồi.", Response::HTTP_CONFLICT);
        }

        // 3. Validate preferred_shift
        $preferredShift = $data["preferred_shift"] ?? null;
        if ($preferredShift !== null && !empty($preferredShift)) {
            $allowedShifts = ["morning", "afternoon", "evening", "weekend", "flexible"];
            if (!in_array($preferredShift, $allowedShifts, true)) {
                throw new ValidationException(["preferred_shift" => ["Ca làm việc mong muốn không hợp lệ. Cho phép: " . implode(", ", $allowedShifts)]]);
            }
        } else {
            $preferredShift = null;
        }

        // 4. Validate cover_letter
        $coverLetter = isset($data["cover_letter"]) ? trim((string)$data["cover_letter"]) : null;
        if ($coverLetter !== null && strlen($coverLetter) > 3000) {
            throw new ValidationException(["cover_letter" => ["Thư ứng tuyển không được vượt quá 3000 ký tự."]]);
        }

        // 4b. Validate ai_match_consent (CV-AI-P0-05)
        // Missing -> false; strictly accepts boolean only. If key exists but is not boolean (null, string, int), throw 422.
        $aiMatchConsent = false;
        if (array_key_exists("ai_match_consent", $data)) {
            $rawConsent = $data["ai_match_consent"];
            if (!is_bool($rawConsent)) {
                throw new ValidationException(["ai_match_consent" => ["Trường ai_match_consent phải là kiểu boolean (true hoặc false)."]]);
            }
            $aiMatchConsent = $rawConsent;
        }

        // Enforce server-owned invariants: client cannot set timestamp or notice version
        $aiMatchConsentedAt = $aiMatchConsent ? date("Y-m-d H:i:s") : null;
        $aiMatchNoticeVersion = $aiMatchConsent ? self::AI_MATCH_NOTICE_VERSION : null;

        // 5. Server-owned active CV selection from student profile (CV-P0-02)
        // Disregard any client-supplied CV parameters (cv_url_snapshot, resume, cv_url, cv_storage_path, file_id, path, user_id)
        $studentProfile = $this->profileRepo->findByUserId($studentUserId);
        $cvStoragePath = $studentProfile["cv_storage_path"] ?? null;
        if (empty($cvStoragePath)) {
            throw new ValidationException(["cv_file" => ["Bạn chưa có CV tải lên trong hồ sơ. Vui lòng tải lên CV định dạng PDF trước khi ứng tuyển."]]);
        }
        if (!$this->cvStorageService->fileExists($cvStoragePath)) {
            throw new ValidationException(["cv_file" => ["Tệp CV trong hồ sơ không tồn tại hoặc đã bị xóa. Vui lòng tải lên lại CV trước khi ứng tuyển."]]);
        }

        $cvOriginalName = $studentProfile["cv_original_name"] ?? "cv.pdf";
        $cvFileSize = isset($studentProfile["cv_file_size"]) && $studentProfile["cv_file_size"] !== null ? (int)$studentProfile["cv_file_size"] : null;
        $cvMimeType = $studentProfile["cv_mime_type"] ?? "application/pdf";

        // 6. Create application Entity & persist with transaction
        // Do NOT copy student_profiles.cv_url into applications.resume or cv_url_snapshot for new applications
        $app = Application::create(
            $jobId,
            $studentUserId,
            $coverLetter,
            null,
            $preferredShift,
            $cvStoragePath,
            $cvOriginalName,
            $cvFileSize,
            $cvMimeType
        );
        $app->setConsent($aiMatchConsent, $aiMatchConsentedAt, $aiMatchNoticeVersion);

        $db = $this->applicationRepo->getDb();
        $db->beginTransaction();
        try {
            $this->applicationRepo->create($app);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            if (str_contains($e->getMessage(), "Duplicate entry")) {
                throw new AppException("Bạn đã ứng tuyển vào vị trí này rồi.", Response::HTTP_CONFLICT);
            }
            throw $e;
        }

        $fresh = $this->applicationRepo->findById($app->getId());

        // Notify Company Owner
        try {
            $company = $this->companyRepo->getById($job["company_id"]);
            $companyOwnerUserId = $company["user_id"] ?? null;
            if ($companyOwnerUserId) {
                $this->notificationService->notify(
                    $companyOwnerUserId,
                    "Đơn ứng tuyển mới",
                    "Có sinh viên vừa nộp đơn ứng tuyển vào vị trí '{$job['title']}'.",
                    "application_received",
                    [
                        "application_id" => $app->getId(),
                        "job_id"         => $jobId,
                        "job_title"      => $job["title"],
                        "applied_at"     => date("Y-m-d H:i:s")
                    ]
                );
            }
        } catch (\Throwable) {
            // Notification failure should not abort application flow
        }

        $persistedApp = Application::fromArray($fresh);
        $res = $persistedApp->toArrayForStudent();
        $res["match_analysis"] = [
            "consent" => $persistedApp->getAiMatchConsent(),
            "status"  => "not_started",
        ];

        return $res;
    }

    /**
     * Company views applications for a specific job
     */
    public function getJobApplications(array $user, string $jobId, array $filters = [], ?Pagination $pagination = null): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền xem danh sách đơn ứng tuyển của công việc.");
        }

        $company = $this->companyRepo->findByUserId($user["id"]);
        if (!$company) {
            throw new AuthorizationException("Không tìm thấy thông tin công ty tương ứng.");
        }

        $job = $this->jobRepo->findById($jobId);
        if (!$job) {
            throw new NotFoundException("Không tìm thấy tin tuyển dụng.");
        }

        // Enforce Company Isolation
        if ($job["company_id"] !== $company["id"]) {
            throw new AuthorizationException("Bạn không có quyền xem đơn ứng tuyển của công việc thuộc công ty khác.");
        }

        $items = $this->applicationRepo->getByJob($jobId, $filters, $pagination);
        $total = $this->applicationRepo->countByJob($jobId, $filters);

        $mapped = array_map(function($row) {
            return Application::fromArray($row)->toArrayForCompany();
        }, $items);

        return [
            "items" => $mapped,
            "total" => $total
        ];
    }

    private function mapCompanyApplication(array $row): array
    {
        $application = Application::fromArray($row)->toArrayForCompany();
        $skillsText = trim((string)($row["student_skill_names"] ?? ""));
        $skillSeparator = "||";
        if ($skillsText === "") {
            $skillsText = trim((string)($row["student_skills"] ?? ""));
            $skillSeparator = ",;\n";
        }
        $application["student_skills"] = ($skillsText === "" || strtolower($skillsText) === "array")
            ? []
            : array_values(array_filter(array_map(
                "trim",
                preg_split($skillSeparator === "||" ? '/\|\|/u' : '/[,;\n]+/u', $skillsText) ?: []
            )));

        $analysisStatus = (string)($row["match_analysis_status"] ?? "");
        $hasConsent = !empty($row["ai_match_consent"]) && empty($row["ai_match_consent_revoked_at"]);
        $coverage = isset($row["match_coverage"]) ? (float)$row["match_coverage"] : null;
        $score = isset($row["match_score"]) ? (float)$row["match_score"] : null;

        if (!$hasConsent) {
            $displayStatus = "not_consented";
        } elseif ($analysisStatus === "") {
            $displayStatus = "not_evaluated";
        } elseif (in_array($analysisStatus, ["processing", "pending"], true)) {
            $displayStatus = "processing";
        } elseif ($analysisStatus === "revoked") {
            $displayStatus = "revoked";
        } elseif (in_array($analysisStatus, ["completed", "partial"], true) && ($coverage === null || $coverage < 60)) {
            $displayStatus = "insufficient_data";
        } elseif ($score !== null && $coverage !== null && $coverage >= 60) {
            $displayStatus = "available";
        } else {
            $displayStatus = "unavailable";
        }

        $application["match_analysis"] = [
            "status" => $displayStatus,
            "score" => $displayStatus === "available" ? (int)round($score) : null,
            "coverage_percent" => $displayStatus === "available" ? (int)round($coverage) : null,
            "classification" => $displayStatus === "available" ? ($row["match_classification"] ?? null) : null,
        ];

        return $application;
    }

    /**
     * Student views their own submitted applications
     */
    public function getMyApplications(array $user, array $filters = [], ?Pagination $pagination = null): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền xem danh sách đơn ứng tuyển cá nhân.");
        }

        $items = $this->applicationRepo->getByStudent($user["id"], $filters, $pagination);
        $total = $this->applicationRepo->countByStudent($user["id"], $filters);

        // Internal employer_note stays private; only student_message is exposed.
        $mapped = array_map(function($row) {
            $application = Application::fromArray($row)->toArrayForStudent();
            $application["status_history"] = $this->applicationRepo->getStatusHistory($row["id"]);
            return $application;
        }, $items);

        return [
            "items" => $mapped,
            "total" => $total
        ];
    }

    /**
     * Company views all applications across all company jobs
     */
    public function getCompanyApplications(array $user, array $filters = [], ?Pagination $pagination = null): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền truy cập endpoint này.");
        }

        $company = $this->companyRepo->findByUserId($user["id"]);
        if (!$company) {
            throw new AuthorizationException("Không tìm thấy thông tin công ty.");
        }

        $items = $this->applicationRepo->getByCompany($company["id"], $filters, $pagination);
        $total = $this->applicationRepo->countByCompany($company["id"], $filters);

        $mapped = array_map(fn(array $row): array => $this->mapCompanyApplication($row), $items);

        return [
            "items" => $mapped,
            "total" => $total
        ];
    }

    /**
     * View single application detail with strict access control
     */
    public function getApplicationDetail(array $user, string $id): array
    {
        $row = $this->applicationRepo->findById($id);
        if (!$row) {
            throw new NotFoundException("Không tìm thấy thông tin đơn ứng tuyển.");
        }

        $role = $user["role"] ?? "";

        if ($role === "student" || $role === "developer") {
            if ($row["developer_id"] !== $user["id"]) {
                throw new AuthorizationException("Bạn không có quyền xem đơn ứng tuyển của sinh viên khác.");
            }
            $result = Application::fromArray($row)->toArrayForStudent();
            $result["status_history"] = $this->applicationRepo->getStatusHistory($id);
            return $result;
        } elseif ($role === "company") {
            $company = $this->companyRepo->findByUserId($user["id"]);
            if (!$company || $row["company_id"] !== $company["id"]) {
                throw new AuthorizationException("Bạn không có quyền xem đơn ứng tuyển của công ty khác.");
            }

            return Application::fromArray($row)->toArrayForCompany();
        }

        throw new AuthorizationException("Quyền truy cập không hợp lệ.");
    }

    /**
     * Company updates application status & optional employer note
     */
    public function updateStatus(array $user, string $id, array $data): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền cập nhật trạng thái đơn ứng tuyển.");
        }

        $company = $this->companyRepo->findByUserId($user["id"]);
        if (!$company) {
            throw new AuthorizationException("Không tìm thấy thông tin công ty.");
        }

        $row = $this->applicationRepo->findById($id);
        if (!$row) {
            throw new NotFoundException("Không tìm thấy thông tin đơn ứng tuyển.");
        }

        // Enforce Company Isolation
        if ($row["company_id"] !== $company["id"]) {
            throw new AuthorizationException("Bạn không có quyền cập nhật đơn ứng tuyển của công việc thuộc công ty khác.");
        }

        $newStatus = $data["status"] ?? "";
        $allowedStatuses = ["interview", "accepted", "rejected"];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new ValidationException(["status" => ["Trạng thái không hợp lệ. Cho phép: " . implode(", ", $allowedStatuses)]]);
        }

        $rawMessage = $data["student_message"] ?? ($data["employer_note"] ?? null);
        $studentMessage = is_scalar($rawMessage) ? trim((string)$rawMessage) : "";
        if ($studentMessage === "") {
            throw new ValidationException(["student_message" => ["Vui lòng nhập nội dung gửi tới sinh viên."]]);
        }
        if (mb_strlen($studentMessage) > 1000) {
            throw new ValidationException(["student_message" => ["Nội dung gửi sinh viên không được vượt quá 1000 ký tự."]]);
        }

        $statusLabels = [
            "interview" => "Mời phỏng vấn", "rejected" => "Chưa phù hợp", "accepted" => "Trúng tuyển",
        ];
        $this->applicationRepo->updateStatus($id, $newStatus, $studentMessage, $user["id"], "company", $statusLabels[$newStatus] ?? null);

        $fresh = $this->applicationRepo->findById($id);

        $notificationId = null;
        try {
            $studentUserId = $row["developer_id"] ?? "";
            if ($studentUserId) {
                $jobTitle = $row["job_title"] ?? "công việc";
                $title = match ($newStatus) {
                    "interview" => "Bạn có lịch phỏng vấn mới",
                    "accepted" => "Chúc mừng, bạn đã được chấp nhận",
                    "rejected" => "Kết quả đơn ứng tuyển",
                };
                $notification = $this->notificationService->notify(
                    $studentUserId,
                    $title,
                    "{$statusLabels[$newStatus]} – {$jobTitle}. Nội dung từ nhà tuyển dụng: {$studentMessage}",
                    "application_decision",
                    [
                        "application_id" => $id,
                        "job_id"         => $row["job_id"] ?? null,
                        "job_title"      => $jobTitle,
                        "new_status"     => $newStatus,
                        "student_message" => $studentMessage,
                        "url"            => "/student/applications",
                        "updated_at"     => date("Y-m-d H:i:s")
                    ]
                );
                $notificationId = $notification->getId();
            }
        } catch (\Throwable) {
        }

        $emailResult = $this->mailService->sendApplicationDecision(
            ["email" => $row["student_email"] ?? "", "name" => $row["student_name"] ?? "Sinh viên"],
            [
                "id" => $id, "job_title" => $row["job_title"] ?? "Vị trí ứng tuyển",
                "company_name" => $row["company_name"] ?? "Nhà tuyển dụng",
            ],
            $newStatus,
            $studentMessage
        );
        try {
            $this->decisionDeliveryRepo->create($id, $newStatus, $studentMessage, $notificationId, $emailResult);
        } catch (\Throwable) {
        }

        $result = Application::fromArray($fresh)->toArrayForCompany();
        $result["delivery"] = ["in_app" => $notificationId !== null, "email_status" => $emailResult["status"] ?? "failed"];
        return $result;
    }

    /**
     * Student withdraws application
     */
    public function withdraw(array $user, string $id): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền rút đơn ứng tuyển.");
        }

        $row = $this->applicationRepo->findById($id);
        if (!$row) {
            throw new NotFoundException("Không tìm thấy thông tin đơn ứng tuyển.");
        }

        // Enforce Student Ownership
        if ($row["developer_id"] !== $user["id"]) {
            throw new AuthorizationException("Bạn không thể rút đơn ứng tuyển của người khác.");
        }

        $currentStatus = $row["status"];
        if ($currentStatus !== "pending") {
            throw new ValidationException(["status" => ["Chỉ có thể rút đơn trước khi nhà tuyển dụng gửi quyết định. Trạng thái hiện tại: {$currentStatus}."]]);
        }

        $this->applicationRepo->withdraw($id, $user["id"], "student");

        $fresh = $this->applicationRepo->findById($id);
        return Application::fromArray($fresh)->toArrayForStudent();
    }

    /**
     * Get protected CV document file info for authorized streaming (CV-P0-02)
     */
    public function getCvDocument(array $user, string $id): array
    {
        $row = $this->applicationRepo->findById($id);
        if (!$row) {
            throw new NotFoundException("Không tìm thấy thông tin đơn ứng tuyển.");
        }

        $role = $user["role"] ?? "";

        // Strictly authorize: student applicant or job-owning employer
        if ($role === "student" || $role === "developer") {
            if ($row["developer_id"] !== $user["id"]) {
                throw new AuthorizationException("Bạn không có quyền truy cập CV của đơn ứng tuyển này.");
            }
        } elseif ($role === "company") {
            $company = $this->companyRepo->findByUserId($user["id"]);
            if (!$company || $row["company_id"] !== $company["id"]) {
                throw new AuthorizationException("Bạn không có quyền truy cập CV của đơn ứng tuyển này.");
            }
        } else {
            // Admin, guest, or any other role forbidden
            throw new AuthorizationException("Bạn không có quyền truy cập tài liệu CV này.");
        }

        $cvStoragePath = $row["cv_storage_path"] ?? null;
        if (empty($cvStoragePath) || !$this->cvStorageService->fileExists($cvStoragePath)) {
            throw new NotFoundException("Tệp CV không tồn tại trên hệ thống.");
        }

        $absolutePath = $this->cvStorageService->getAbsolutePath($cvStoragePath);
        $originalName = $row["cv_original_name"] ?? "cv.pdf";
        $mimeType = $row["cv_mime_type"] ?? "application/pdf";
        $fileSize = isset($row["cv_file_size"]) && $row["cv_file_size"] !== null
            ? (int)$row["cv_file_size"]
            : (file_exists($absolutePath) ? filesize($absolutePath) : 0);

        return [
            "path"          => $absolutePath,
            "original_name" => $originalName,
            "mime_type"     => $mimeType,
            "file_size"     => $fileSize,
        ];
    }
}
