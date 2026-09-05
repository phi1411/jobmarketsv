<?php

namespace JobMarket\Domain\SavedSearch;

class SavedSearch
{
    private string $id;
    private string $user_id; // Represents student_user_id
    private string $name;
    private ?string $keyword = null;
    private ?string $category_id = null;
    private ?string $location_id = null;
    private ?string $work_type = null;
    private ?string $work_mode = null;
    private ?int $salary_min = null;
    private ?int $salary_max = null;
    private ?array $skill_ids = null;
    private ?string $shift_type = null;
    private bool $notification_enabled = true;
    private string $frequency = "daily";
    private ?string $created_at = null;
    private ?string $updated_at = null;

    public function __construct(
        string $user_id,
        string $name,
        ?string $id = null
    ) {
        $this->id = $id ?? ("ss-" . uniqid());
        $this->user_id = $user_id;
        $this->name = $name;
        $this->created_at = date("Y-m-d H:i:s");
    }

    public static function create(string $user_id, string $name): static
    {
        return new static($user_id, $name);
    }

    public static function fromArray(array $data): static
    {
        $id = $data["id"] ?? ("ss-" . uniqid());
        $userId = $data["user_id"] ?? ($data["student_user_id"] ?? "");
        $name = $data["name"] ?? "Tìm kiếm đã lưu";

        $search = new static($userId, $name, $id);
        $search->keyword = $data["keyword"] ?? null;
        $search->category_id = $data["category_id"] ?? null;
        $search->location_id = $data["location_id"] ?? null;
        $search->work_type = $data["work_type"] ?? null;
        $search->work_mode = $data["work_mode"] ?? null;
        $search->salary_min = isset($data["salary_min"]) && is_numeric($data["salary_min"]) ? (int)$data["salary_min"] : null;
        $search->salary_max = isset($data["salary_max"]) && is_numeric($data["salary_max"]) ? (int)$data["salary_max"] : null;

        $skillIds = $data["skill_ids"] ?? null;
        if (is_string($skillIds)) {
            $decoded = json_decode($skillIds, true);
            $search->skill_ids = is_array($decoded) ? $decoded : null;
        } elseif (is_array($skillIds)) {
            $search->skill_ids = $skillIds;
        }

        $search->shift_type = $data["shift_type"] ?? null;
        $search->notification_enabled = isset($data["notification_enabled"]) ? (bool)$data["notification_enabled"] : true;
        $search->frequency = $data["frequency"] ?? "daily";
        $search->created_at = $data["created_at"] ?? null;
        $search->updated_at = $data["updated_at"] ?? null;

        return $search;
    }

    public function toArray(): array
    {
        return [
            "id"                   => $this->id,
            "student_user_id"      => $this->user_id,
            "name"                 => $this->name,
            "keyword"              => $this->keyword,
            "category_id"          => $this->category_id,
            "location_id"          => $this->location_id,
            "work_type"            => $this->work_type,
            "work_mode"            => $this->work_mode,
            "salary_min"           => $this->salary_min,
            "salary_max"           => $this->salary_max,
            "skill_ids"            => $this->skill_ids,
            "shift_type"           => $this->shift_type,
            "notification_enabled" => $this->notification_enabled,
            "frequency"            => $this->frequency,
            "created_at"           => $this->created_at,
            "updated_at"           => $this->updated_at,
        ];
    }

    // Getters & Setters
    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->user_id; }
    public function getName(): string { return $this->name; }
    public function getKeyword(): ?string { return $this->keyword; }
    public function getCategoryId(): ?string { return $this->category_id; }
    public function getLocationId(): ?string { return $this->location_id; }
    public function getWorkType(): ?string { return $this->work_type; }
    public function getWorkMode(): ?string { return $this->work_mode; }
    public function getSalaryMin(): ?int { return $this->salary_min; }
    public function getSalaryMax(): ?int { return $this->salary_max; }
    public function getSkillIds(): ?array { return $this->skill_ids; }
    public function getShiftType(): ?string { return $this->shift_type; }
    public function isNotificationEnabled(): bool { return $this->notification_enabled; }
    public function getFrequency(): string { return $this->frequency; }

    public function setName(string $name): self { $this->name = $name; return $this; }
    public function setKeyword(?string $keyword): self { $this->keyword = $keyword; return $this; }
    public function setCategoryId(?string $catId): self { $this->category_id = $catId; return $this; }
    public function setLocationId(?string $locId): self { $this->location_id = $locId; return $this; }
    public function setWorkType(?string $type): self { $this->work_type = $type; return $this; }
    public function setWorkMode(?string $mode): self { $this->work_mode = $mode; return $this; }
    public function setSalaryMin(?int $min): self { $this->salary_min = $min; return $this; }
    public function setSalaryMax(?int $max): self { $this->salary_max = $max; return $this; }
    public function setSkillIds(?array $skills): self { $this->skill_ids = $skills; return $this; }
    public function setShiftType(?string $shift): self { $this->shift_type = $shift; return $this; }
    public function setNotificationEnabled(bool $enabled): self { $this->notification_enabled = $enabled; return $this; }
    public function setFrequency(string $frequency): self { $this->frequency = $frequency; return $this; }
}