<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Profile\Profile;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\ProfileRepository;
use JobMarket\Support\Pagination;
use PDO;

class ProfileService
{
    private ProfileRepositoryInterface $profileRepository;
    private PDO $db;

    public function __construct(?ProfileRepositoryInterface $profileRepository = null, ?PDO $db = null)
    {
        $this->profileRepository = $profileRepository ?? new ProfileRepository();

        if ($db !== null) {
            $this->db = $db;
        } else {
            $config = Config::env();
            $this->db = new PDO(
                "mysql:dbname={$config['dbname']};host={$config['host']};port={$config['port']};charset=utf8mb4",
                $config["user"],
                $config["password"],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
    }

    public function getMyProfile(array $user): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền truy cập hồ sơ sinh viên cá nhân.");
        }

        $userId = $user["id"] ?? "";
        $existing = $this->profileRepository->findByUserId($userId);

        if (!$existing) {
            // Return initial skeleton for new student
            $profile = new Profile($userId);
            $profile->setFullName($user["name"] ?? null);
            $profile->setEmail($user["email"] ?? null);
            return $profile->toArrayPrivate();
        }

        $profile = Profile::fromArray($existing);
        $profile->setEmail($existing["email"] ?? ($user["email"] ?? null));

        return $profile->toArrayPrivate();
    }

    public function updateMyProfile(array $user, array $data): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền cập nhật hồ sơ sinh viên.");
        }

        $userId = $user["id"] ?? "";
        $existing = $this->profileRepository->findByUserId($userId);

        $profileId = $existing["id"] ?? ("prof-" . uniqid());
        $profile = $existing ? Profile::fromArray($existing) : new Profile($userId, $profileId);

        // Discard any client-supplied sensitive/immutable fields (Anti-Mass Assignment)
        unset(
            $data["user_id"],
            $data["role"],
            $data["profile_completion_percent"],
            $data["id"],
            $data["email"],
            $data["cv_storage_path"],
            $data["cv_original_name"],
            $data["cv_mime_type"],
            $data["cv_file_size"],
            $data["cv_uploaded_at"],
            $data["active_cv"],
            $data["cv_url"],
            $data["created_at"],
            $data["updated_at"]
        );

        $errors = [];

        // 1. Validate full_name
        if (isset($data["full_name"])) {
            $name = trim((string)$data["full_name"]);
            if (strlen($name) < 2) {
                $errors["full_name"][] = "Họ và tên phải có tối thiểu 2 ký tự.";
            } elseif (strlen($name) > 150) {
                $errors["full_name"][] = "Họ và tên không được vượt quá 150 ký tự.";
            } else {
                $profile->setFullName($name);
            }
        }

        // 2. Validate phone
        if (isset($data["phone"]) && !empty($data["phone"])) {
            $phone = trim((string)$data["phone"]);
            if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
                $errors["phone"][] = "Số điện thoại không đúng định dạng (từ 9 đến 15 chữ số).";
            } else {
                $profile->setPhone($phone);
            }
        }

        // 3. Validate date_of_birth
        if (isset($data["date_of_birth"]) && !empty($data["date_of_birth"])) {
            $dob = trim((string)$data["date_of_birth"]);
            $time = strtotime($dob);
            if ($time === false || $time > time()) {
                $errors["date_of_birth"][] = "Ngày sinh không hợp lệ.";
            } else {
                $profile->setDateOfBirth($dob);
            }
        }

        // 4. Validate gender
        if (isset($data["gender"]) && !empty($data["gender"])) {
            $gender = strtolower(trim((string)$data["gender"]));
            if (!in_array($gender, ["male", "female", "other"], true)) {
                $errors["gender"][] = "Giới tính không hợp lệ. Cho phép: male, female, other.";
            } else {
                $profile->setGender($gender);
            }
        }

        // 5. University & Major
        if (isset($data["university"])) {
            $profile->setUniversity(trim((string)$data["university"]));
        }
        if (isset($data["major"])) {
            $profile->setMajor(trim((string)$data["major"]));
        }

        // 6. Academic year
        if (isset($data["academic_year"]) && !empty($data["academic_year"])) {
            $year = (int)$data["academic_year"];
            if ($year < 1 || $year > 7) {
                $errors["academic_year"][] = "Năm học phải là số từ 1 đến 7.";
            } else {
                $profile->setAcademicYear($year);
            }
        }

        // 7. Bio
        if (isset($data["bio"])) {
            $bio = trim((string)$data["bio"]);
            if (strlen($bio) > 2000) {
                $errors["bio"][] = "Giới thiệu bản thân không được vượt quá 2000 ký tự.";
            } else {
                $profile->setBio($bio);
            }
        }

        // 8. Location ID (Check exists in locations table)
        if (isset($data["location_id"]) && !empty($data["location_id"])) {
            $locId = trim((string)$data["location_id"]);
            $stmt = $this->db->prepare("SELECT `name` FROM `locations` WHERE `id` = ? LIMIT 1");
            $stmt->execute([$locId]);
            $locationName = $stmt->fetchColumn();

            if (!$locationName) {
                $errors["location_id"][] = "Địa điểm (location_id) không tồn tại trong hệ thống.";
            } else {
                $profile->setLocationId($locId);
                if (empty($profile->getPreferredLocation())) {
                    $profile->setPreferredLocation($locationName);
                }
            }
        }

        if (isset($data["preferred_location"])) {
            $profile->setPreferredLocation(trim((string)$data["preferred_location"]));
        }
        if (isset($data["preferred_locations"])) {
            $profile->setPreferredLocations(trim((string)$data["preferred_locations"]));
        }

        // 9. Skills (skill_ids or skills array/string)
        $rawSkills = $data["skill_ids"] ?? ($data["skills"] ?? null);
        if ($rawSkills !== null) {
            if (is_string($rawSkills) && !str_starts_with(trim($rawSkills), "[")) {
                $profile->setSkills(trim($rawSkills));
            } else {
                $skillIds = $rawSkills;
                if (is_string($skillIds)) {
                    $decoded = json_decode($skillIds, true);
                    $skillIds = is_array($decoded) ? $decoded : [$skillIds];
                }

                if (!is_array($skillIds)) {
                    $errors["skill_ids"][] = "Danh sách kỹ năng (skill_ids) phải là một mảng.";
                } elseif (!empty($skillIds)) {
                    $skillIds = array_values(array_unique(array_filter($skillIds)));
                    $inQuery = implode(',', array_fill(0, count($skillIds), '?'));
                    $stmt = $this->db->prepare("SELECT `id`, `name` FROM `skills` WHERE `id` IN ($inQuery)");
                    $stmt->execute($skillIds);
                    $foundSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($foundSkills) !== count($skillIds)) {
                        $errors["skill_ids"][] = "Một hoặc nhiều kỹ năng (skill_ids) không tồn tại trong hệ thống.";
                    } else {
                        $profile->setSkillIds($skillIds);
                        $skillNames = array_column($foundSkills, "name");
                        $profile->setSkills(implode(", ", $skillNames));
                    }
                } else {
                    $profile->setSkillIds([]);
                }
            }
        }

        // 10. Availability Schedule (Validate JSON / structure)
        $schedule = $data["availability_schedule"] ?? ($data["available_schedule"] ?? null);
        if ($schedule !== null) {
            if (is_string($schedule)) {
                if (strlen($schedule) > 5000) {
                    $errors["availability_schedule"][] = "Lịch rảnh vượt quá dung lượng cho phép.";
                } else {
                    $decoded = json_decode($schedule, true);
                    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                        $errors["availability_schedule"][] = "Lịch rảnh (availability_schedule) không đúng định dạng JSON hợp lệ.";
                    } else {
                        $profile->setAvailableSchedule(is_array($decoded) ? $decoded : [$schedule]);
                    }
                }
            } elseif (is_array($schedule)) {
                $profile->setAvailableSchedule($schedule);
            } else {
                $errors["availability_schedule"][] = "Lịch rảnh phải là mảng hoặc JSON hợp lệ.";
            }
        }

        // 11. Work Experience, Education, Certificates.
        // Explicit null/empty values clear data; structured payloads are strict,
        // bounded and canonicalized before persistence. Plain legacy text remains supported.
        if (array_key_exists("work_experience", $data)) {
            [$valid, $value, $fieldErrors] = $this->normalizeStructuredListField(
                $data["work_experience"],
                'work_experience',
                20,
                10000,
                ['title' => 255, 'company' => 255, 'duration' => 100, 'description' => 2000]
            );
            if ($valid) {
                $profile->setWorkExperience($value);
            } else {
                $errors['work_experience'] = $fieldErrors;
            }
        }

        if (array_key_exists("education", $data)) {
            [$valid, $value, $fieldErrors] = $this->normalizeStructuredObjectField(
                $data["education"],
                'education',
                5000,
                [
                    'university' => 255,
                    'major' => 255,
                    'degree' => 100,
                    'grad_year' => 20,
                    'description' => 2000,
                ]
            );
            if ($valid) {
                $profile->setEducation($value);
            } else {
                $errors['education'] = $fieldErrors;
            }
        }

        if (array_key_exists("certificates", $data)) {
            [$valid, $value, $fieldErrors] = $this->normalizeStructuredListField(
                $data["certificates"],
                'certificates',
                20,
                5000,
                ['name' => 255, 'year' => 100]
            );
            if ($valid) {
                $profile->setCertificates($value);
            } else {
                $errors['certificates'] = $fieldErrors;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Upsert profile
        $this->profileRepository->upsert($profile);

        // Fetch fresh and return
        $fresh = $this->profileRepository->findByUserId($userId);
        $freshProfile = Profile::fromArray($fresh);
        $freshProfile->setEmail($fresh["email"] ?? ($user["email"] ?? null));

        return $freshProfile->toArrayPrivate();
    }

    /**
     * @param array<string, int> $fieldLimits
     * @return array{0: bool, 1: ?string, 2: list<string>}
     */
    private function normalizeStructuredListField(
        mixed $raw,
        string $field,
        int $maxItems,
        int $maxTotalLength,
        array $fieldLimits
    ): array {
        if ($this->isExplicitlyEmpty($raw)) {
            return [true, null, []];
        }

        [$structured, $value, $decodeErrors] = $this->decodeStructuredValue($raw, $field);
        if ($decodeErrors !== []) {
            return [false, null, $decodeErrors];
        }
        if (!$structured) {
            return $this->normalizeLegacyText($value, $field, $maxTotalLength);
        }
        if (!is_array($value) || !array_is_list($value)) {
            return [false, null, ["{$field} phải là danh sách JSON."]];
        }
        if (count($value) > $maxItems) {
            return [false, null, ["Số lượng mục {$field} không được vượt quá {$maxItems}."]];
        }

        $normalized = [];
        $errors = [];
        foreach ($value as $index => $item) {
            if (!is_array($item) || array_is_list($item)) {
                $errors[] = "Mục {$field} số " . ($index + 1) . " phải là một object JSON.";
                continue;
            }

            $unknown = array_diff(array_keys($item), array_keys($fieldLimits));
            if ($unknown !== []) {
                $errors[] = "Mục {$field} số " . ($index + 1) . " chứa trường không được hỗ trợ.";
                continue;
            }

            $normalizedItem = [];
            foreach ($fieldLimits as $key => $limit) {
                if (!array_key_exists($key, $item) || $item[$key] === null) {
                    $normalizedItem[$key] = '';
                    continue;
                }
                if (!is_string($item[$key]) && !is_int($item[$key]) && !is_float($item[$key])) {
                    $errors[] = "Trường {$key} trong mục {$field} số " . ($index + 1) . " sai kiểu dữ liệu.";
                    continue 2;
                }
                $text = trim((string)$item[$key]);
                if (mb_strlen($text, 'UTF-8') > $limit) {
                    $errors[] = "Trường {$key} trong mục {$field} số " . ($index + 1) . " vượt quá {$limit} ký tự.";
                    continue 2;
                }
                $normalizedItem[$key] = $text;
            }

            if (array_filter($normalizedItem, static fn(string $itemValue): bool => $itemValue !== '') !== []) {
                $normalized[] = $normalizedItem;
            }
        }

        if ($errors !== []) {
            return [false, null, $errors];
        }
        if ($normalized === []) {
            return [true, null, []];
        }

        return $this->encodeStructuredValue($normalized, $field, $maxTotalLength);
    }

    /**
     * @param array<string, int> $fieldLimits
     * @return array{0: bool, 1: ?string, 2: list<string>}
     */
    private function normalizeStructuredObjectField(
        mixed $raw,
        string $field,
        int $maxTotalLength,
        array $fieldLimits
    ): array {
        if ($this->isExplicitlyEmpty($raw)) {
            return [true, null, []];
        }

        [$structured, $value, $decodeErrors] = $this->decodeStructuredValue($raw, $field);
        if ($decodeErrors !== []) {
            return [false, null, $decodeErrors];
        }
        if (!$structured) {
            return $this->normalizeLegacyText($value, $field, $maxTotalLength);
        }
        if (!is_array($value) || $value === [] || array_is_list($value)) {
            return $value === []
                ? [true, null, []]
                : [false, null, ["{$field} phải là một object JSON."]];
        }

        $unknown = array_diff(array_keys($value), array_keys($fieldLimits));
        if ($unknown !== []) {
            return [false, null, ["{$field} chứa trường không được hỗ trợ."]];
        }

        $normalized = [];
        foreach ($fieldLimits as $key => $limit) {
            if (!array_key_exists($key, $value) || $value[$key] === null) {
                $normalized[$key] = '';
                continue;
            }
            if (!is_string($value[$key]) && !is_int($value[$key]) && !is_float($value[$key])) {
                return [false, null, ["Trường {$key} trong {$field} sai kiểu dữ liệu."]];
            }
            $text = trim((string)$value[$key]);
            if (mb_strlen($text, 'UTF-8') > $limit) {
                return [false, null, ["Trường {$key} trong {$field} vượt quá {$limit} ký tự."]];
            }
            $normalized[$key] = $text;
        }

        if (array_filter($normalized, static fn(string $itemValue): bool => $itemValue !== '') === []) {
            return [true, null, []];
        }

        return $this->encodeStructuredValue($normalized, $field, $maxTotalLength);
    }

    private function isExplicitlyEmpty(mixed $raw): bool
    {
        return $raw === null
            || (is_string($raw) && trim($raw) === '')
            || (is_array($raw) && $raw === []);
    }

    /**
     * @return array{0: bool, 1: mixed, 2: list<string>}
     */
    private function decodeStructuredValue(mixed $raw, string $field): array
    {
        if (is_array($raw)) {
            return [true, $raw, []];
        }
        if (!is_string($raw)) {
            return [false, null, ["{$field} phải là chuỗi hoặc dữ liệu JSON hợp lệ."]];
        }

        $trimmed = trim($raw);
        $firstCharacter = $trimmed[0] ?? '';
        if ($firstCharacter !== '[' && $firstCharacter !== '{') {
            return [false, $trimmed, []];
        }

        try {
            $decoded = json_decode($trimmed, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [true, null, ["{$field} không đúng định dạng JSON hợp lệ."]];
        }

        return [true, $decoded, []];
    }

    /** @return array{0: bool, 1: ?string, 2: list<string>} */
    private function normalizeLegacyText(mixed $value, string $field, int $maxLength): array
    {
        if (!is_string($value)) {
            return [false, null, ["{$field} sai kiểu dữ liệu."]];
        }
        $trimmed = trim($value);
        if (mb_strlen($trimmed, 'UTF-8') > $maxLength) {
            return [false, null, ["Dung lượng {$field} vượt quá {$maxLength} ký tự."]];
        }
        return [true, $trimmed === '' ? null : $trimmed, []];
    }

    /** @return array{0: bool, 1: ?string, 2: list<string>} */
    private function encodeStructuredValue(array $value, string $field, int $maxLength): array
    {
        try {
            $encoded = json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\JsonException) {
            return [false, null, ["{$field} chứa dữ liệu không thể mã hóa."]];
        }
        if (mb_strlen($encoded, 'UTF-8') > $maxLength) {
            return [false, null, ["Dung lượng {$field} vượt quá {$maxLength} ký tự."]];
        }
        return [true, $encoded, []];
    }

    public function getPublicProfile(string $id): array
    {
        $row = $this->profileRepository->findById($id);
        if (!$row) {
            throw new NotFoundException("Không tìm thấy thông tin hồ sơ ứng viên.");
        }

        $profile = Profile::fromArray($row);

        return $profile->toArrayPublic();
    }

    public function getPublicProfiles(array $filters = [], ?Pagination $pagination = null): array
    {
        $items = $this->profileRepository->searchPublic($filters, $pagination);
        $total = $this->profileRepository->countPublic($filters);

        $publicItems = array_map(function($row) {
            return Profile::fromArray($row)->toArrayPublic();
        }, $items);

        return [
            "items" => $publicItems,
            "total" => $total
        ];
    }
}
