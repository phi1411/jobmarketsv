<?php

namespace JobMarket\Domain\Application;

class Application
{
    private string $id;
    private string $job_id;
    private string $developer_id; // Represents student_user_id
    private ?string $cover_letter = null;
    private ?string $resume = null; // Represents legacy cv_url_snapshot
    private ?string $preferred_shift = null;
    private string $status = "pending";
    private ?string $employer_note = null;
    private ?string $student_message = null;
    private ?string $applied_at = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // CV Snapshot metadata (CV-P0-02)
    private ?string $cv_storage_path = null;
    private ?string $cv_original_name = null;
    private ?int $cv_file_size = null;
    private ?string $cv_mime_type = null;

    // AI Match Consent metadata (CV-AI-P0-04 & CV-AI-P0-05)
    private bool $ai_match_consent = false;
    private ?string $ai_match_consented_at = null;
    private ?string $ai_match_consent_revoked_at = null;
    private ?string $ai_match_notice_version = null;

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
        ?string $preferred_shift = null,
        ?string $cv_storage_path = null,
        ?string $cv_original_name = null,
        ?int $cv_file_size = null,
        ?string $cv_mime_type = null
    ): static {
        $app = new static($job_id, $developer_id, $cover_letter, $resume, "pending");
        $app->preferred_shift = $preferred_shift;
        $app->cv_storage_path = $cv_storage_path;
        $app->cv_original_name = $cv_original_name;
        $app->cv_file_size = $cv_file_size;
        $app->cv_mime_type = $cv_mime_type;
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
        $app->student_message = $data["student_message"] ?? null;
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

        // CV Snapshot
        $app->cv_storage_path = $data["cv_storage_path"] ?? null;
        $app->cv_original_name = $data["cv_original_name"] ?? null;
        $app->cv_file_size = isset($data["cv_file_size"]) && $data["cv_file_size"] !== null ? (int)$data["cv_file_size"] : null;
        $app->cv_mime_type = $data["cv_mime_type"] ?? null;

        // Consent metadata
        $app->ai_match_consent = (bool)($data["ai_match_consent"] ?? false);
        $app->ai_match_consented_at = $data["ai_match_consented_at"] ?? null;
        $app->ai_match_consent_revoked_at = $data["ai_match_consent_revoked_at"] ?? null;
        $app->ai_match_notice_version = $data["ai_match_notice_version"] ?? null;

        return $app;
    }

    public function canBeWithdrawn(): bool
    {
        return $this->status === "pending";
    }

    public function withdraw(): void
    {
        $this->status = "withdrawn";
    }

    public function updateStatus(string $status, ?string $studentMessage = null): void
    {
        $this->status = $status;
        if ($studentMessage !== null) {
            $this->student_message = $studentMessage;
        }
    }

    /**
     * Presentation array for Student:
     * Returns only the explicit message intended for the student; internal employer_note stays private.
     * Never exposes raw cv_storage_path.
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
            "has_cv_snapshot" => !empty($this->cv_storage_path),
            "cv_file_name"    => $this->cv_original_name,
            "cv_file_size"    => $this->cv_file_size,
            "cv_mime_type"    => $this->cv_mime_type,
            "preferred_shift" => $this->preferred_shift,
            "status"          => $this->status,
            "student_message" => $this->student_message,
            "ai_match_consent" => $this->ai_match_consent,
            "ai_match_consented_at" => $this->ai_match_consented_at,
            "ai_match_notice_version" => $this->ai_match_notice_version,
            "match_analysis"  => [
                "consent" => $this->ai_match_consent,
                "status"  => "not_started",
            ],
            "applied_at"      => $this->applied_at,
            "created_at"      => $this->created_at,
            "updated_at"      => $this->updated_at,
        ];
    }

    /**
     * Presentation array for Employer / Company:
     * Includes employer_note, student details, and CV snapshot indicators.
     * Never exposes raw cv_storage_path.
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
            "has_cv_snapshot"    => !empty($this->cv_storage_path),
            "cv_file_name"       => $this->cv_original_name,
            "cv_file_size"       => $this->cv_file_size,
            "cv_mime_type"       => $this->cv_mime_type,
            "preferred_shift"    => $this->preferred_shift,
            "status"             => $this->status,
            "employer_note"      => $this->employer_note,
            "student_message"    => $this->student_message,
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
    public function getStudentMessage(): ?string { return $this->student_message; }
    public function getAppliedAt(): ?string { return $this->applied_at; }
    public function getJobTitle(): ?string { return $this->job_title; }
    public function getCompanyId(): ?string { return $this->company_id; }
    public function getCompanyName(): ?string { return $this->company_name; }

    // CV Snapshot getters
    public function getCvStoragePath(): ?string { return $this->cv_storage_path; }
    public function getCvOriginalName(): ?string { return $this->cv_original_name; }
    public function getCvFileSize(): ?int { return $this->cv_file_size; }
    public function getCvMimeType(): ?string { return $this->cv_mime_type; }

    public function setPreferredShift(?string $shift): self { $this->preferred_shift = $shift; return $this; }
    public function setEmployerNote(?string $note): self { $this->employer_note = $note; return $this; }
    public function setStudentMessage(?string $message): self { $this->student_message = $message; return $this; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    // AI Match Consent getters & setters
    public function getAiMatchConsent(): bool { return $this->ai_match_consent; }
    public function getAiMatchConsentedAt(): ?string { return $this->ai_match_consented_at; }
    public function getAiMatchConsentRevokedAt(): ?string { return $this->ai_match_consent_revoked_at; }
    public function getAiMatchNoticeVersion(): ?string { return $this->ai_match_notice_version; }

    public function setConsent(bool $consent, ?string $consentedAt = null, ?string $noticeVersion = null): self
    {
        $this->ai_match_consent = $consent;
        $this->ai_match_consented_at = $consent ? ($consentedAt ?? date("Y-m-d H:i:s")) : null;
        $this->ai_match_notice_version = $consent ? ($noticeVersion ?? 'ai-match.v1') : null;
        if (!$consent) {
            $this->ai_match_consent_revoked_at = null;
        }
        return $this;
    }
}
