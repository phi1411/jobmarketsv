<?php

namespace JobMarket\Domain\Application;

class Application
{
    private string $id;
    private string $job_id;
    private string $developer_id; // Represents student_user_id
    private ?string $cover_letter = null;
    private ?string $resume = null; // Represents cv_url_snapshot
    private ?string $preferred_shift = null;
    private string $status = "pending";
    private ?string $employer_note = null;
    private ?string $applied_at = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // Joined metadata (read-only for presentation)
    private ?string $job_title = null;
    private ?string $company_id = null;
    private ?string $company_name = null;
    private ?string $student_name = null;
    private ?string $student_email = null;
    private ?string $student_phone = null;
    private ?string $student_university = null;
    private ?string $student_major = null;

    public function __construct(
        string $job_id,
        string $developer_id,
        ?string $cover_letter = null,
        ?string $resume = null,
        string $status = "pending",
        ?string $id = null
    ) {
        $this->id = $id ?? ("app-" . uniqid());
        $this->job_id = $job_id;
        $this->developer_id = $developer_id;
        $this->cover_letter = $cover_letter;
        $this->resume = $resume;
        $this->status = $status;
        $this->applied_at = date("Y-m-d H:i:s");
    }

    public static function create(
        string $job_id,
        string $developer_id,
        ?string $cover_letter = null,
        ?string $resume = null,
        ?string $preferred_shift = null
    ): static {
        $app = new static($job_id, $developer_id, $cover_letter, $resume, "pending");
        $app->preferred_shift = $preferred_shift;
        return $app;
    }

    public static function fromArray(array $data): static
    {
        $id = $data["id"] ?? ("app-" . uniqid());
        $jobId = $data["job_id"] ?? "";
        $devId = $data["developer_id"] ?? ($data["student_user_id"] ?? "");
        $cover = $data["cover_letter"] ?? null;
        $resume = $data["resume"] ?? ($data["cv_url_snapshot"] ?? null);
        $status = $data["status"] ?? "pending";

        $app = new static($jobId, $devId, $cover, $resume, $status, $id);
        $app->preferred_shift = $data["preferred_shift"] ?? null;
        $app->employer_note = $data["employer_note"] ?? null;
        $app->applied_at = $data["applied_at"] ?? null;
        $app->created_at = $data["created_at"] ?? null;
        $app->updated_at = $data["updated_at"] ?? null;

        // Joined data
        $app->job_title = $data["job_title"] ?? null;
        $app->company_id = $data["company_id"] ?? null;
        $app->company_name = $data["company_name"] ?? null;
        $app->student_name = $data["student_name"] ?? ($data["user_name"] ?? null);
        $app->student_email = $data["student_email"] ?? ($data["email"] ?? null);
        $app->student_phone = $data["student_phone"] ?? ($data["phone"] ?? null);
        $app->student_university = $data["student_university"] ?? ($data["university"] ?? null);
        $app->student_major = $data["student_major"] ?? ($data["major"] ?? null);

        return $app;
    }

    public function canBeWithdrawn(): bool
    {
        return in_array($this->status, ["pending", "viewed", "reviewed"], true);
    }

    public function withdraw(): void
    {
        $this->status = "withdrawn";
    }

    public function updateStatus(string $status, ?string $note = null): void
    {
        $this->status = $status;
        if ($note !== null) {
            $this->employer_note = $note;
        }
    }

    /**
     * Presentation array for Student:
     * NEVER returns employer_note or internal company private notes.
     */
    public function toArrayForStudent(): array
    {
        return [
            "id"              => $this->id,
            "job_id"          => $this->job_id,
            "job_title"       => $this->job_title,
            "company_name"    => $this->company_name,
            "student_user_id" => $this->developer_id,
            "cover_letter"    => $this->cover_letter,
            "cv_url_snapshot" => $this->resume,
            "resume"          => $this->resume,
            "preferred_shift" => $this->preferred_shift,
            "status"          => $this->status,
            "applied_at"      => $this->applied_at,
            "created_at"      => $this->created_at,
            "updated_at"      => $this->updated_at,
        ];
    }

    /**
     * Presentation array for Employer / Company:
     * Includes employer_note, student details, and CV snapshot.
     */
    public function toArrayForCompany(): array
    {
        return [
            "id"                 => $this->id,
            "job_id"             => $this->job_id,
            "job_title"          => $this->job_title,
            "student_user_id"    => $this->developer_id,
            "student_name"       => $this->student_name,
            "student_email"      => $this->student_email,
            "student_phone"      => $this->student_phone,
            "student_university" => $this->student_university,
            "student_major"      => $this->student_major,
            "cover_letter"       => $this->cover_letter,
            "cv_url_snapshot"    => $this->resume,
            "resume"             => $this->resume,
            "preferred_shift"    => $this->preferred_shift,
            "status"             => $this->status,
            "employer_note"      => $this->employer_note,
            "applied_at"         => $this->applied_at,
            "created_at"         => $this->created_at,
            "updated_at"         => $this->updated_at,
        ];
    }

    // Getters & Setters
    public function getId(): string { return $this->id; }
    public function getJobId(): string { return $this->job_id; }
    public function getDeveloperId(): string { return $this->developer_id; }
    public function getCoverLetter(): ?string { return $this->cover_letter; }
    public function getResume(): ?string { return $this->resume; }
    public function getPreferredShift(): ?string { return $this->preferred_shift; }
    public function getStatus(): string { return $this->status; }
    public function getEmployerNote(): ?string { return $this->employer_note; }
    public function getAppliedAt(): ?string { return $this->applied_at; }
    public function getJobTitle(): ?string { return $this->job_title; }
    public function getCompanyId(): ?string { return $this->company_id; }
    public function getCompanyName(): ?string { return $this->company_name; }

    public function setPreferredShift(?string $shift): self { $this->preferred_shift = $shift; return $this; }
    public function setEmployerNote(?string $note): self { $this->employer_note = $note; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}
