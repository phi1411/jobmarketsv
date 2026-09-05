<?php

namespace JobMarket\Domain\Job;

class Job
{
    private string $id;
    private string $company_id;
    private ?string $category_id = null;
    private ?string $location_id = null;
    private string $title;
    private ?string $description = null;
    private ?string $requirements = null;
    private ?string $benefits = null;
    private ?string $location = null;
    private ?string $city = null;
    private ?string $district = null;
    private ?string $address = null;
    private string $status = "published";
    private string $work_type = "part_time";
    private string $work_mode = "onsite";
    private string $salary_type = "hourly";
    private ?int $salary_min = null;
    private ?int $salary_max = null;
    private string $currency = "VND";
    private string $shift_type = "morning";
    private ?string $shift_information = null;
    private ?string $working_schedule = null;
    private ?string $required_skills = null;
    private int $quantity = 1;
    private ?string $application_deadline = null;
    private ?string $rejection_reason = null;
    private ?string $published_at = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    private ?string $deleted_at = null;

    // Legacy aliases
    private ?string $category = null;
    private ?string $type = null;

    public function __construct(
        string $company_id,
        string $title,
        ?string $description = null,
        ?string $requirements = null,
        ?string $location = null,
        ?string $category = null,
        string $type = "part-time",
        ?string $id = null
    ) {
        $this->id = $id ?? ("job-" . uniqid());
        $this->company_id = $company_id;
        $this->title = $title;
        $this->description = $description;
        $this->requirements = $requirements;
        $this->location = $location;
        $this->category = $category;
        $this->category_id = $category;
        $this->type = $type;
        $this->work_type = str_replace("-", "_", $type);
    }

    public static function create(
        string $company_id,
        string $title,
        ?string $description = null,
        ?string $requirements = null,
        ?string $location = null,
        ?string $category = null,
        string $type = "part-time"
    ): static {
        return new static($company_id, $title, $description, $requirements, $location, $category, $type);
    }

    public static function fromArray(array $data): static
    {
        $id = $data["id"] ?? ("job-" . uniqid());
        $companyId = $data["company_id"] ?? "";
        $title = $data["title"] ?? "";

        $job = new static($companyId, $title, $data["description"] ?? null, $data["requirements"] ?? null, $data["location"] ?? null, $data["category_id"] ?? ($data["category"] ?? null), $data["work_type"] ?? ($data["type"] ?? "part_time"), $id);

        $job->benefits = $data["benefits"] ?? null;
        $job->location_id = $data["location_id"] ?? null;
        $job->city = $data["city"] ?? null;
        $job->district = $data["district"] ?? null;
        $job->address = $data["address"] ?? null;
        $job->status = $data["status"] ?? "published";
        $job->work_type = $data["work_type"] ?? "part_time";
        $job->work_mode = $data["work_mode"] ?? ($data["work_format"] ?? "onsite");
        $job->salary_type = $data["salary_type"] ?? "hourly";
        $job->salary_min = isset($data["salary_min"]) ? (int)$data["salary_min"] : null;
        $job->salary_max = isset($data["salary_max"]) ? (int)$data["salary_max"] : null;
        $job->currency = $data["currency"] ?? "VND";
        $job->shift_type = $data["shift_type"] ?? "morning";
        $job->shift_information = $data["shift_information"] ?? null;
        $job->working_schedule = $data["working_schedule"] ?? null;
        if (isset($data["required_skills"])) {
            if (is_array($data["required_skills"])) {
                $filtered = array_values(array_filter(array_map(fn($item) => is_string($item) || is_numeric($item) ? trim((string)$item) : "", $data["required_skills"]), fn($s) => $s !== ""));
                $job->required_skills = empty($filtered) ? null : json_encode($filtered, JSON_UNESCAPED_UNICODE);
            } elseif (is_string($data["required_skills"])) {
                $trimmed = trim($data["required_skills"]);
                $job->required_skills = $trimmed === "" ? null : $trimmed;
            } else {
                $job->required_skills = null;
            }
        } else {
            $job->required_skills = null;
        }
        $job->quantity = isset($data["quantity"]) ? (int)$data["quantity"] : 1;
        $job->application_deadline = $data["application_deadline"] ?? ($data["deadline"] ?? null);
        $job->rejection_reason = $data["rejection_reason"] ?? null;
        $job->published_at = $data["published_at"] ?? null;
        $job->created_at = $data["created_at"] ?? null;
        $job->updated_at = $data["updated_at"] ?? null;
        $job->deleted_at = $data["deleted_at"] ?? null;

        return $job;
    }

    public function toArray(): array
    {
        return [
            "id"                   => $this->id,
            "company_id"           => $this->company_id,
            "category_id"          => $this->category_id,
            "location_id"          => $this->location_id,
            "title"                => $this->title,
            "description"          => $this->description,
            "requirements"         => $this->requirements,
            "benefits"             => $this->benefits,
            "location"             => $this->location,
            "city"                 => $this->city,
            "district"             => $this->district,
            "address"              => $this->address,
            "status"               => $this->status,
            "work_type"            => $this->work_type,
            "work_mode"            => $this->work_mode,
            "salary_type"          => $this->salary_type,
            "salary_min"           => $this->salary_min,
            "salary_max"           => $this->salary_max,
            "currency"             => $this->currency,
            "shift_type"           => $this->shift_type,
            "shift_information"    => $this->shift_information,
            "working_schedule"     => $this->working_schedule,
            "required_skills"      => $this->required_skills,
            "skills"               => $this->getRequiredSkillsArray(),
            "quantity"             => $this->quantity,
            "application_deadline" => $this->application_deadline,
            "deadline"             => $this->application_deadline,
            "rejection_reason"     => $this->rejection_reason,
            "published_at"         => $this->published_at,
            "created_at"           => $this->created_at,
            "updated_at"           => $this->updated_at,
            "deleted_at"           => $this->deleted_at,
        ];
    }

    // Domain Invariants & Rules
    public function isExpired(): bool
    {
        if (empty($this->application_deadline)) {
            return false;
        }
        return strtotime($this->application_deadline) < strtotime(date("Y-m-d"));
    }

    public function isClosed(): bool
    {
        return $this->status === "closed" || $this->isExpired();
    }

    public function close(): self
    {
        $this->status = "closed";
        $this->updated_at = date("Y-m-d H:i:s");
        return $this;
    }

    public function publish(): self
    {
        $this->status = "published";
        $this->published_at = date("Y-m-d H:i:s");
        $this->updated_at = date("Y-m-d H:i:s");
        return $this;
    }

    public function canAcceptApplications(): bool
    {
        return $this->status === "published" && !$this->isExpired();
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getCompanyId(): string { return $this->company_id; }
    public function getCategoryId(): ?string { return $this->category_id; }
    public function getLocationId(): ?string { return $this->location_id; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getRequirements(): ?string { return $this->requirements; }
    public function getBenefits(): ?string { return $this->benefits; }
    public function getLocation(): ?string { return $this->location; }
    public function getCity(): ?string { return $this->city; }
    public function getDistrict(): ?string { return $this->district; }
    public function getAddress(): ?string { return $this->address; }
    public function getStatus(): string { return $this->status; }
    public function getWorkType(): string { return $this->work_type; }
    public function getWorkMode(): string { return $this->work_mode; }
    public function getSalaryType(): string { return $this->salary_type; }
    public function getSalaryMin(): ?int { return $this->salary_min; }
    public function getSalaryMax(): ?int { return $this->salary_max; }
    public function getCurrency(): string { return $this->currency; }
    public function getShiftType(): string { return $this->shift_type; }
    public function getShiftInformation(): ?string { return $this->shift_information; }
    public function getWorkingSchedule(): ?string { return $this->working_schedule; }
    public function getRequiredSkills(): ?string { return $this->required_skills; }
    public function getRequiredSkillsArray(): array
    {
        if (empty($this->required_skills)) {
            return [];
        }
        $decoded = json_decode($this->required_skills, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return array_values(array_filter(array_map('trim', explode(',', $this->required_skills)), fn($s) => $s !== ''));
    }
    public function getQuantity(): int { return $this->quantity; }
    public function getApplicationDeadline(): ?string { return $this->application_deadline; }
    public function getRejectionReason(): ?string { return $this->rejection_reason; }
    public function getPublishedAt(): ?string { return $this->published_at; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    public function getDeletedAt(): ?string { return $this->deleted_at; }

    // Legacy Getters
    public function getCategory(): ?string { return $this->category_id ?? $this->category; }
    public function getType(): ?string { return $this->work_type; }

    // Setters
    public function setId(string $id): self { $this->id = $id; return $this; }
    public function setCompanyId(string $company_id): self { $this->company_id = $company_id; return $this; }
    public function setCategoryId(?string $category_id): self { $this->category_id = $category_id; return $this; }
    public function setLocationId(?string $location_id): self { $this->location_id = $location_id; return $this; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function setRequirements(?string $requirements): self { $this->requirements = $requirements; return $this; }
    public function setBenefits(?string $benefits): self { $this->benefits = $benefits; return $this; }
    public function setLocation(?string $location): self { $this->location = $location; return $this; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }
    public function setDistrict(?string $district): self { $this->district = $district; return $this; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function setWorkType(string $work_type): self { $this->work_type = $work_type; return $this; }
    public function setWorkMode(string $work_mode): self { $this->work_mode = $work_mode; return $this; }
    public function setSalaryType(string $salary_type): self { $this->salary_type = $salary_type; return $this; }
    public function setSalaryMin(?int $salary_min): self { $this->salary_min = $salary_min; return $this; }
    public function setSalaryMax(?int $salary_max): self { $this->salary_max = $salary_max; return $this; }
    public function setCurrency(string $currency): self { $this->currency = $currency; return $this; }
    public function setShiftType(string $shift_type): self { $this->shift_type = $shift_type; return $this; }
    public function setShiftInformation(?string $info): self { $this->shift_information = $info; return $this; }
    public function setWorkingSchedule(?string $schedule): self { $this->working_schedule = $schedule; return $this; }
    public function setRequiredSkills(string|array|null $skills): self
    {
        if (is_array($skills)) {
            $filtered = array_values(array_filter(array_map(fn($item) => is_string($item) || is_numeric($item) ? trim((string)$item) : "", $skills), fn($s) => $s !== ""));
            $this->required_skills = empty($filtered) ? null : json_encode($filtered, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($skills)) {
            $trimmed = trim($skills);
            $this->required_skills = $trimmed === "" ? null : $trimmed;
        } else {
            $this->required_skills = null;
        }
        return $this;
    }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }
    public function setApplicationDeadline(?string $deadline): self { $this->application_deadline = $deadline; return $this; }
    public function setRejectionReason(?string $reason): self { $this->rejection_reason = $reason; return $this; }
    public function setPublishedAt(?string $published_at): self { $this->published_at = $published_at; return $this; }
    public function setDeletedAt(?string $deleted_at): self { $this->deleted_at = $deleted_at; return $this; }

    // Legacy Setters
    public function setCategory(?string $category): self { $this->category = $category; $this->category_id = $category; return $this; }
    public function setType(string $type): self { $this->type = $type; $this->work_type = str_replace("-", "_", $type); return $this; }
}