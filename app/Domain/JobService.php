<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Job\Job;
use JobMarket\Domain\Job\JobRepositoryInterface;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Infrastructure\CompanyRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Support\Logger;
use JobMarket\Support\Pagination;

class JobService
{
    private JobRepositoryInterface $jobRepository;
    private CompanyRepository $companyRepository;

    public function __construct(?JobRepositoryInterface $jobRepository = null, ?CompanyRepository $companyRepository = null)
    {
        $this->jobRepository = $jobRepository ?? new JobRepository();
        $this->companyRepository = $companyRepository ?? new CompanyRepository();
    }

    public function getAll(): array
    {
        return $this->jobRepository->getAll();
    }

    public function search(array $filters = [], ?Pagination $pagination = null): array
    {
        $items = $this->jobRepository->search($filters, $pagination);
        $total = $this->jobRepository->count($filters);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    public function getById(string $id, bool $allowAnyStatus = false): array
    {
        $job = $this->jobRepository->findById($id);
        if (empty($job)) {
            throw new NotFoundException("Không tìm thấy thông tin việc làm với mã: {$id}");
        }

        // For public viewing: only published and not expired
        if (!$allowAnyStatus) {
            if ($job["status"] !== "published") {
                throw new NotFoundException("Tin tuyển dụng hiện không khả dụng hoặc đã đóng.");
            }
            if (!empty($job["application_deadline"]) && strtotime($job["application_deadline"]) < strtotime(date("Y-m-d"))) {
                throw new NotFoundException("Tin tuyển dụng đã hết hạn nộp hồ sơ.");
            }
        }

        return $job;
    }

    public function createJob(array $data, array $user): array
    {
        // 1. Role enforcement
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng / Công ty mới có quyền tạo tin việc làm.");
        }

        // 2. Resolve ownership from authenticated user
        $company = $this->companyRepository->findByUserId($user["id"]);
        if (!$company) {
            throw new ValidationException(["company" => ["Tài khoản chưa được liên kết với bất kỳ hồ sơ công ty nào."]]);
        }

        $companyId = $company["id"];

        // 3. Domain validation
        $this->validateJobData($data, $company);

        // 4. Determine status & published_at
        $status = $data["status"] ?? "published";
        $publishedAt = null;

        if ($status === "published") {
            if ($company["verification_status"] !== "verified") {
                throw new ValidationException(["status" => ["Công ty của bạn chưa được xác minh (verified) nên chưa thể công khai tin tuyển dụng. Vui lòng chọn lưu nháp (draft) hoặc chờ duyệt (pending_approval)."]]);
            }
            $publishedAt = date("Y-m-d H:i:s");
        }

        $data["company_id"] = $companyId;
        $data["status"] = $status;
        $data["published_at"] = $publishedAt;

        $job = Job::fromArray($data);
        $this->jobRepository->create($job);

        $created = $this->jobRepository->findById($job->getId());
        if ($status === "published") {
            $this->processJobAlertsSafely($created);
        }

        return $created;
    }

    public function updateJob(string $id, array $data, array $user): array
    {
        // 1. Check existing job
        $existing = $this->jobRepository->findById($id);
        if (empty($existing)) {
            throw new NotFoundException("Không tìm thấy tin việc làm cần cập nhật.");
        }

        // 2. Resolve ownership
        $company = $this->companyRepository->findByUserId($user["id"]);
        if (!$company || $existing["company_id"] !== $company["id"]) {
            throw new AuthorizationException("Bạn không có quyền chỉnh sửa tin tuyển dụng của công ty khác.");
        }

        // 3. Domain validation
        $mergedData = array_merge($existing, $data);
        $this->validateJobData($mergedData, $company);

        // 4. Verification check if publishing
        $status = $mergedData["status"] ?? $existing["status"];
        if ($status === "published" && $company["verification_status"] !== "verified") {
            throw new ValidationException(["status" => ["Công ty chưa được xác minh nên không thể chuyển tin sang trạng thái công khai (published)."]]);
        }

        $isNewlyPublished = ($existing["status"] ?? "") !== "published" && $status === "published";
        if ($isNewlyPublished) {
            $mergedData["published_at"] = date("Y-m-d H:i:s");
        }

        $mergedData["id"] = $id;
        $job = Job::fromArray($mergedData);
        $this->jobRepository->update($job);

        $updated = $this->jobRepository->findById($id);
        if ($isNewlyPublished) {
            $this->processJobAlertsSafely($updated);
        }

        return $updated;
    }

    public function closeJob(string $id, array $user): array
    {
        $existing = $this->jobRepository->findById($id);
        if (empty($existing)) {
            throw new NotFoundException("Không tìm thấy tin việc làm để đóng.");
        }

        $company = $this->companyRepository->findByUserId($user["id"]);
        if (!$company || $existing["company_id"] !== $company["id"]) {
            throw new AuthorizationException("Bạn không có quyền đóng tin tuyển dụng này.");
        }

        $this->jobRepository->close($id);

        return $this->jobRepository->findById($id);
    }

    public function deleteJob(string $id, array $user): void
    {
        $existing = $this->jobRepository->findById($id);
        if (empty($existing)) {
            throw new NotFoundException("Không tìm thấy tin việc làm để xóa.");
        }

        $company = $this->companyRepository->findByUserId($user["id"]);
        if (!$company || $existing["company_id"] !== $company["id"]) {
            throw new AuthorizationException("Bạn không có quyền xóa tin tuyển dụng này.");
        }

        $this->jobRepository->delete($id);
    }

    public function getCompanyJobs(string $companyId, array $filters = [], ?Pagination $pagination = null, bool $isOwner = false): array
    {
        $filters["company_id"] = $companyId;
        $filters["is_public"] = !$isOwner;

        $items = $this->jobRepository->search($filters, $pagination);
        $total = $this->jobRepository->count($filters);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    public function getMyJobs(array $user, array $filters = [], ?Pagination $pagination = null): array
    {
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Công ty mới có danh sách tin nội bộ.");
        }

        $company = $this->companyRepository->findByUserId($user["id"]);
        if (!$company) {
            return ["items" => [], "total" => 0];
        }

        $filters["company_id"] = $company["id"];
        $filters["is_public"] = false; // Owner can see all statuses

        $items = $this->jobRepository->search($filters, $pagination);
        $total = $this->jobRepository->count($filters);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    private function validateJobData(array $data, array $company): void
    {
        $errors = [];

        // Title
        if (empty($data["title"]) || strlen(trim($data["title"])) < 5) {
            $errors["title"][] = "Tiêu đề việc làm bắt buộc và phải có tối thiểu 5 ký tự.";
        }

        // Description
        if (empty($data["description"]) || strlen(trim($data["description"])) < 10) {
            $errors["description"][] = "Mô tả công việc bắt buộc và phải có tối thiểu 10 ký tự.";
        }

        // Application Deadline not in past
        $deadline = $data["application_deadline"] ?? ($data["deadline"] ?? null);
        if (!empty($deadline)) {
            $deadlineTime = strtotime($deadline);
            if ($deadlineTime === false || $deadlineTime < strtotime(date("Y-m-d"))) {
                $errors["application_deadline"][] = "Hạn nộp hồ sơ không được ở trong quá khứ.";
            }
        }

        // Salary Min <= Salary Max
        $salaryMin = isset($data["salary_min"]) ? (int)$data["salary_min"] : null;
        $salaryMax = isset($data["salary_max"]) ? (int)$data["salary_max"] : null;

        if ($salaryMin !== null && $salaryMin < 0) {
            $errors["salary_min"][] = "Mức lương tối thiểu không được âm.";
        }
        if ($salaryMax !== null && $salaryMax < 0) {
            $errors["salary_max"][] = "Mức lương tối đa không được âm.";
        }
        if ($salaryMin !== null && $salaryMax !== null && $salaryMin > $salaryMax) {
            $errors["salary_min"][] = "Mức lương tối thiểu không được lớn hơn mức lương tối đa.";
        }

        // Age requirement is optional. Blank means the criterion is automatically met.
        $minimumAge = isset($data["minimum_age"]) && $data["minimum_age"] !== "" ? (int)$data["minimum_age"] : null;
        $maximumAge = isset($data["maximum_age"]) && $data["maximum_age"] !== "" ? (int)$data["maximum_age"] : null;
        if ($minimumAge !== null && ($minimumAge < 15 || $minimumAge > 80)) {
            $errors["minimum_age"][] = "Tuổi tối thiểu phải từ 15 đến 80.";
        }
        if ($maximumAge !== null && ($maximumAge < 15 || $maximumAge > 80)) {
            $errors["maximum_age"][] = "Tuổi tối đa phải từ 15 đến 80.";
        }
        if ($minimumAge !== null && $maximumAge !== null && $minimumAge > $maximumAge) {
            $errors["minimum_age"][] = "Tuổi tối thiểu không được lớn hơn tuổi tối đa.";
        }

        // Work Type Whitelist
        if (!empty($data["work_type"])) {
            $allowedWorkTypes = ["part_time", "internship", "freelance", "part-time"];
            if (!in_array($data["work_type"], $allowedWorkTypes, true)) {
                $errors["work_type"][] = "Hình thức làm việc (work_type) không hợp lệ. Cho phép: part_time, internship, freelance.";
            }
        }

        // Work Mode Whitelist
        if (!empty($data["work_mode"])) {
            $allowedWorkModes = ["onsite", "remote", "hybrid", "on-site"];
            if (!in_array($data["work_mode"], $allowedWorkModes, true)) {
                $errors["work_mode"][] = "Chế độ làm việc (work_mode) không hợp lệ. Cho phép: onsite, remote, hybrid.";
            }
        }

        // Salary Type Whitelist
        if (!empty($data["salary_type"])) {
            $allowedSalaryTypes = ["hourly", "daily", "monthly", "negotiable"];
            if (!in_array($data["salary_type"], $allowedSalaryTypes, true)) {
                $errors["salary_type"][] = "Loại lương (salary_type) không hợp lệ. Cho phép: hourly, daily, monthly, negotiable.";
            }
        }

        // Shift Type Whitelist
        if (!empty($data["shift_type"])) {
            $allowedShifts = ["morning", "afternoon", "evening", "night", "rotating", "weekend", "flexible"];
            if (!in_array($data["shift_type"], $allowedShifts, true)) {
                $errors["shift_type"][] = "Ca làm việc (shift_type) không hợp lệ. Cho phép: morning, afternoon, evening, night, rotating, weekend, flexible.";
            }
        }

        // Status Whitelist
        if (!empty($data["status"])) {
            $allowedStatuses = ["draft", "pending_approval", "published", "rejected", "closed", "expired"];
            if (!in_array($data["status"], $allowedStatuses, true)) {
                $errors["status"][] = "Trạng thái tin (status) không hợp lệ.";
            }
        }

        // Required Skills Validation
        if (array_key_exists("required_skills", $data) && $data["required_skills"] !== null) {
            $skills = $data["required_skills"];
            if (is_array($skills)) {
                if (count($skills) > 30) {
                    $errors["required_skills"][] = "Số lượng kỹ năng yêu cầu không được vượt quá 30.";
                }
                foreach ($skills as $s) {
                    if (!is_string($s) && !is_numeric($s)) {
                        $errors["required_skills"][] = "Kỹ năng yêu cầu phải là chuỗi ký tự.";
                        break;
                    }
                    $trimS = trim((string)$s);
                    if ($trimS === "") {
                        $errors["required_skills"][] = "Kỹ năng yêu cầu không được chứa phần tử rỗng.";
                        break;
                    }
                    if (strlen($trimS) > 100) {
                        $errors["required_skills"][] = "Mỗi kỹ năng yêu cầu không được vượt quá 100 ký tự.";
                        break;
                    }
                }
            } elseif (is_string($skills)) {
                if (strlen($skills) > 2000) {
                    $errors["required_skills"][] = "Chuỗi kỹ năng yêu cầu không được vượt quá 2000 ký tự.";
                }
            } else {
                $errors["required_skills"][] = "Kỹ năng yêu cầu (required_skills) phải là mảng hoặc chuỗi ký tự hợp lệ.";
            }
        }

        // If status is published, require essential fields
        $status = $data["status"] ?? "published";
        if ($status === "published") {
            if (empty($data["category_id"]) && empty($data["category"])) {
                $errors["category_id"][] = "Tin tuyển dụng công khai bắt buộc phải chọn danh mục ngành nghề.";
            }
            if (empty($deadline)) {
                $errors["application_deadline"][] = "Tin tuyển dụng công khai bắt buộc phải có hạn nộp hồ sơ.";
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function processJobAlertsSafely(array $job): void
    {
        try {
            (new JobAlertService())->processPublishedJob($job);
        } catch (\Throwable $e) {
            // An optional delivery channel must not make a valid job publication fail.
            Logger::error("Lỗi xử lý thông báo việc làm phù hợp sau khi đăng tin.", [
                "job_id" => $job["id"] ?? null,
                "error" => $e->getMessage(),
            ]);
        }

        try {
            (new ProfileJobAlertService())->processPublishedJob($job);
        } catch (\Throwable $e) {
            Logger::error("Lỗi xử lý thông báo cá nhân hóa sau khi đăng tin.", [
                "job_id" => $job["id"] ?? null,
                "error" => $e->getMessage(),
            ]);
        }
    }
}
