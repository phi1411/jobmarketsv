<?php

namespace JobMarket\Domain;

use JobMarket\Domain\SavedSearch\SavedSearch;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\SavedSearchRepository;
use JobMarket\Support\Pagination;
use PDO;

class SaveSearchService
{
    private SavedSearchRepository $savedSearchRepo;
    private PDO $db;

    public function __construct(?SavedSearchRepository $savedSearchRepo = null)
    {
        $this->savedSearchRepo = $savedSearchRepo ?? new SavedSearchRepository();

        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function create(array $user, array $data): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền tạo tìm kiếm đã lưu.");
        }

        $userId = $user["id"] ?? "";
        $this->validateSearchCriteria($data, true);

        $name = trim((string)$data["name"]);
        $search = SavedSearch::create($userId, $name);

        $this->hydrateSearchFromData($search, $data);
        $this->savedSearchRepo->create($search);

        $fresh = $this->savedSearchRepo->findById($search->getId());
        return SavedSearch::fromArray($fresh)->toArray();
    }

    public function list(array $user, ?Pagination $pagination = null): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền xem danh sách tìm kiếm đã lưu.");
        }

        $userId = $user["id"] ?? "";
        $rows = $this->savedSearchRepo->getByUser($userId, $pagination);
        $total = $this->savedSearchRepo->countByUser($userId);

        $items = array_map(function($row) {
            return SavedSearch::fromArray($row)->toArray();
        }, $rows);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    public function update(array $user, string $id, array $data): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền cập nhật tìm kiếm đã lưu.");
        }

        $row = $this->savedSearchRepo->findById($id);
        if (!$row) {
            throw new NotFoundException("Không tìm thấy bộ lọc tìm kiếm đã lưu.");
        }

        // Ownership enforcement
        if ($row["user_id"] !== $user["id"]) {
            throw new AuthorizationException("Bạn không có quyền chỉnh sửa bộ lọc tìm kiếm của người khác.");
        }

        $this->validateSearchCriteria($data, false);

        $search = SavedSearch::fromArray($row);
        $this->hydrateSearchFromData($search, $data);

        $this->savedSearchRepo->update($search);

        $fresh = $this->savedSearchRepo->findById($id);
        return SavedSearch::fromArray($fresh)->toArray();
    }

    public function delete(array $user, string $id): void
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền xóa tìm kiếm đã lưu.");
        }

        $row = $this->savedSearchRepo->findById($id);
        if (!$row) {
            throw new NotFoundException("Không tìm thấy bộ lọc tìm kiếm đã lưu.");
        }

        // Ownership enforcement
        if ($row["user_id"] !== $user["id"]) {
            throw new AuthorizationException("Bạn không có quyền xóa bộ lọc tìm kiếm của người khác.");
        }

        $this->savedSearchRepo->delete($id);
    }

    private function validateSearchCriteria(array $data, bool $isCreate): void
    {
        $errors = [];

        if ($isCreate || isset($data["name"])) {
            $name = trim((string)($data["name"] ?? ""));
            if (empty($name)) {
                $errors["name"][] = "Tên bộ lọc tìm kiếm là bắt buộc.";
            } elseif (strlen($name) > 150) {
                $errors["name"][] = "Tên bộ lọc tìm kiếm không được vượt quá 150 ký tự.";
            }
        }

        // Validate category_id
        if (isset($data["category_id"]) && !empty($data["category_id"])) {
            $catId = trim((string)$data["category_id"]);
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `categories` WHERE `id` = ?");
            $stmt->execute([$catId]);
            if (((int)$stmt->fetchColumn()) === 0) {
                $errors["category_id"][] = "Danh mục việc làm (category_id) không tồn tại trong hệ thống.";
            }
        }

        // Validate location_id
        if (isset($data["location_id"]) && !empty($data["location_id"])) {
            $locId = trim((string)$data["location_id"]);
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `locations` WHERE `id` = ?");
            $stmt->execute([$locId]);
            if (((int)$stmt->fetchColumn()) === 0) {
                $errors["location_id"][] = "Địa điểm (location_id) không tồn tại trong hệ thống.";
            }
        }

        // Validate skill_ids
        if (isset($data["skill_ids"])) {
            $skillIds = $data["skill_ids"];
            if (is_string($skillIds)) {
                $decoded = json_decode($skillIds, true);
                $skillIds = is_array($decoded) ? $decoded : [$skillIds];
            }

            if (!is_array($skillIds)) {
                $errors["skill_ids"][] = "Danh sách kỹ năng (skill_ids) phải là một mảng.";
            } elseif (!empty($skillIds)) {
                $skillIds = array_values(array_unique(array_filter($skillIds)));
                $inQuery = implode(',', array_fill(0, count($skillIds), '?'));
                $stmt = $this->db->prepare("SELECT `id` FROM `skills` WHERE `id` IN ($inQuery)");
                $stmt->execute($skillIds);
                $found = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (count($found) !== count($skillIds)) {
                    $errors["skill_ids"][] = "Một hoặc nhiều kỹ năng (skill_ids) không tồn tại trong hệ thống.";
                }
            }
        }

        // Validate salary range
        if (isset($data["salary_min"]) && isset($data["salary_max"])) {
            if (is_numeric($data["salary_min"]) && is_numeric($data["salary_max"])) {
                if ((int)$data["salary_min"] > (int)$data["salary_max"]) {
                    $errors["salary_min"][] = "Mức lương tối thiểu không được lớn hơn mức lương tối đa.";
                }
            }
        }

        // Validate enums
        if (isset($data["work_type"]) && !empty($data["work_type"])) {
            $allowedWorkTypes = ["part_time", "full_time", "internship", "freelance"];
            if (!in_array($data["work_type"], $allowedWorkTypes, true)) {
                $errors["work_type"][] = "Hình thức làm việc (work_type) không hợp lệ.";
            }
        }

        if (isset($data["work_mode"]) && !empty($data["work_mode"])) {
            $allowedWorkModes = ["onsite", "remote", "hybrid"];
            if (!in_array($data["work_mode"], $allowedWorkModes, true)) {
                $errors["work_mode"][] = "Chế độ làm việc (work_mode) không hợp lệ.";
            }
        }

        if (isset($data["shift_type"]) && !empty($data["shift_type"])) {
            $allowedShifts = ["morning", "afternoon", "evening", "night", "flexible", "weekend"];
            if (!in_array($data["shift_type"], $allowedShifts, true)) {
                $errors["shift_type"][] = "Ca làm việc (shift_type) không hợp lệ.";
            }
        }

        if (isset($data["frequency"]) && !empty($data["frequency"])) {
            if (!in_array($data["frequency"], ["instant", "daily", "weekly"], true)) {
                $errors["frequency"][] = "Tần suất email phải là instant, daily hoặc weekly.";
            }
        }

        if (isset($data["minimum_match_score"])) {
            $score = filter_var($data["minimum_match_score"], FILTER_VALIDATE_INT);
            if ($score === false || $score < 30 || $score > 100) {
                $errors["minimum_match_score"][] = "Mức độ phù hợp tối thiểu phải từ 30% đến 100%.";
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function hydrateSearchFromData(SavedSearch $search, array $data): void
    {
        if (isset($data["name"])) {
            $search->setName(trim((string)$data["name"]));
        }
        if (array_key_exists("keyword", $data)) {
            $search->setKeyword(!empty($data["keyword"]) ? trim((string)$data["keyword"]) : null);
        }
        if (array_key_exists("category_id", $data)) {
            $search->setCategoryId(!empty($data["category_id"]) ? trim((string)$data["category_id"]) : null);
        }
        if (array_key_exists("location_id", $data)) {
            $search->setLocationId(!empty($data["location_id"]) ? trim((string)$data["location_id"]) : null);
        }
        if (array_key_exists("work_type", $data)) {
            $search->setWorkType(!empty($data["work_type"]) ? trim((string)$data["work_type"]) : null);
        }
        if (array_key_exists("work_mode", $data)) {
            $search->setWorkMode(!empty($data["work_mode"]) ? trim((string)$data["work_mode"]) : null);
        }
        if (array_key_exists("salary_min", $data)) {
            $search->setSalaryMin(is_numeric($data["salary_min"]) ? (int)$data["salary_min"] : null);
        }
        if (array_key_exists("salary_max", $data)) {
            $search->setSalaryMax(is_numeric($data["salary_max"]) ? (int)$data["salary_max"] : null);
        }
        if (array_key_exists("skill_ids", $data)) {
            $skillIds = $data["skill_ids"];
            if (is_string($skillIds)) {
                $skillIds = json_decode($skillIds, true);
            }
            $search->setSkillIds(is_array($skillIds) ? array_values(array_unique(array_filter($skillIds))) : null);
        }
        if (array_key_exists("shift_type", $data)) {
            $search->setShiftType(!empty($data["shift_type"]) ? trim((string)$data["shift_type"]) : null);
        }
        if (isset($data["notification_enabled"])) {
            $search->setNotificationEnabled((bool)$data["notification_enabled"]);
        }
        if (isset($data["email_enabled"])) {
            $search->setEmailEnabled((bool)$data["email_enabled"]);
        }
        if (isset($data["minimum_match_score"])) {
            $search->setMinimumMatchScore((int)$data["minimum_match_score"]);
        }
        if (isset($data["frequency"])) {
            $search->setFrequency(trim((string)$data["frequency"]));
        }
    }
}
