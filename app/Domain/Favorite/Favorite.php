<?php

namespace JobMarket\Domain\Favorite;

class Favorite
{
    private string $id;
    private string $user_id;
    private string $job_id;
    private ?string $created_at = null;

    // Joined Job Details
    private ?string $job_title = null;
    private ?string $company_id = null;
    private ?string $company_name = null;
    private ?string $company_logo = null;
    private ?string $location = null;
    private ?string $city = null;
    private ?string $district = null;
    private ?int $salary_min = null;
    private ?int $salary_max = null;
    private ?string $salary_type = null;
    private ?string $shift_type = null;
    private ?string $work_type = null;
    private ?string $application_deadline = null;

    public function __construct(string $user_id, string $job_id, ?string $id = null)
    {
        $this->id = $id ?? ("fav-" . uniqid());
        $this->user_id = $user_id;
        $this->job_id = $job_id;
        $this->created_at = date("Y-m-d H:i:s");
    }

    public static function create(string $user_id, string $job_id): static
    {
        return new static($user_id, $job_id);
    }

    public static function fromArray(array $data): static
    {
        $id = $data["id"] ?? ("fav-" . uniqid());
        $userId = $data["user_id"] ?? "";
        $jobId = $data["job_id"] ?? "";

        $fav = new static($userId, $jobId, $id);
        $fav->created_at = $data["created_at"] ?? null;

        // Joined job info
        $fav->job_title = $data["job_title"] ?? ($data["title"] ?? null);
        $fav->company_id = $data["company_id"] ?? null;
        $fav->company_name = $data["company_name"] ?? null;
        $fav->company_logo = $data["company_logo"] ?? null;
        $fav->location = $data["location"] ?? null;
        $fav->city = $data["city"] ?? null;
        $fav->district = $data["district"] ?? null;
        $fav->salary_min = isset($data["salary_min"]) ? (int)$data["salary_min"] : null;
        $fav->salary_max = isset($data["salary_max"]) ? (int)$data["salary_max"] : null;
        $fav->salary_type = $data["salary_type"] ?? null;
        $fav->shift_type = $data["shift_type"] ?? null;
        $fav->work_type = $data["work_type"] ?? null;
        $fav->application_deadline = $data["application_deadline"] ?? null;

        return $fav;
    }

    public function toArray(): array
    {
        return [
            "id"                   => $this->id,
            "user_id"              => $this->user_id,
            "job_id"               => $this->job_id,
            "job_title"            => $this->job_title,
            "company_id"           => $this->company_id,
            "company_name"         => $this->company_name,
            "company_logo"         => $this->company_logo,
            "location"             => $this->location,
            "city"                 => $this->city,
            "district"             => $this->district,
            "salary_min"           => $this->salary_min,
            "salary_max"           => $this->salary_max,
            "salary_type"          => $this->salary_type,
            "shift_type"           => $this->shift_type,
            "work_type"            => $this->work_type,
            "application_deadline" => $this->application_deadline,
            "created_at"           => $this->created_at,
        ];
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->user_id; }
    public function getJobId(): string { return $this->job_id; }
    public function getCreatedAt(): ?string { return $this->created_at; }
}
