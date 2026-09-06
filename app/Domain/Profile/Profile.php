<?php

namespace JobMarket\Domain\Profile;

class Profile
{
    private string $id;
    private string $user_id;
    private ?string $full_name = null;
    private ?string $phone = null;
    private ?string $date_of_birth = null;
    private ?string $gender = null;
    private ?string $university = null;
    private ?string $major = null;
    private ?int $academic_year = null;
    private ?string $bio = null;
    private ?string $location_id = null;
    private ?string $preferred_location = null;
    private ?string $preferred_locations = null;
    private ?array $available_schedule = null;
    private ?string $skills = null;
    private ?array $skill_ids = null;
    private ?string $work_experience = null;
    private ?string $education = null;
    private ?string $certificates = null;
    private ?string $cv_url = null;
    private ?string $cv_storage_path = null;
    private ?string $cv_original_name = null;
    private ?string $cv_mime_type = null;
    private ?int $cv_file_size = null;
    private ?string $cv_uploaded_at = null;
    private int $profile_completion_percent = 0;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // Optional associated user data
    private ?string $email = null;

    public function __construct(string $user_id, ?string $id = null)
    {
        $this->id = $id ?? ("prof-" . uniqid());
        $this->user_id = $user_id;
    }

    public static function create(string $user_id): static
    {
        return new static($user_id);
    }

    public static function fromArray(array $data): static
    {
        $id = $data["id"] ?? ("prof-" . uniqid());
        $userId = $data["user_id"] ?? "";

        $profile = new static($userId, $id);
        $profile->full_name = $data["full_name"] ?? ($data["name"] ?? null);
        $profile->phone = $data["phone"] ?? null;
        $profile->date_of_birth = $data["date_of_birth"] ?? null;
        $profile->gender = $data["gender"] ?? null;
        $profile->university = $data["university"] ?? null;
        $profile->major = $data["major"] ?? null;
        $profile->academic_year = isset($data["academic_year"]) ? (int)$data["academic_year"] : null;
        $profile->bio = $data["bio"] ?? null;
        $profile->location_id = $data["location_id"] ?? null;
        $profile->preferred_location = $data["preferred_location"] ?? null;
        $profile->preferred_locations = $data["preferred_locations"] ?? null;

        // Schedule
        $schedule = $data["available_schedule"] ?? ($data["availability_schedule"] ?? null);
        if (is_string($schedule)) {
            $decoded = json_decode($schedule, true);
            $profile->available_schedule = is_array($decoded) ? $decoded : null;
        } elseif (is_array($schedule)) {
            $profile->available_schedule = $schedule;
        }

        // Skills
        $profile->skills = $data["skills"] ?? null;
        $skillIds = $data["skill_ids"] ?? null;
        if (is_string($skillIds)) {
            $decoded = json_decode($skillIds, true);
            $profile->skill_ids = is_array($decoded) ? $decoded : null;
        } elseif (is_array($skillIds)) {
            $profile->skill_ids = $skillIds;
        }

        $profile->work_experience = $data["work_experience"] ?? null;
        $profile->education = $data["education"] ?? null;
        $profile->certificates = $data["certificates"] ?? null;
        $profile->cv_url = $data["cv_url"] ?? null;
        $profile->cv_storage_path = $data["cv_storage_path"] ?? null;
        $profile->cv_original_name = $data["cv_original_name"] ?? null;
        $profile->cv_mime_type = $data["cv_mime_type"] ?? null;
        $profile->cv_file_size = isset($data["cv_file_size"]) && $data["cv_file_size"] !== null ? (int)$data["cv_file_size"] : null;
        $profile->cv_uploaded_at = $data["cv_uploaded_at"] ?? null;
        $profile->email = $data["email"] ?? null;
        $profile->created_at = $data["created_at"] ?? null;
        $profile->updated_at = $data["updated_at"] ?? null;

        $profile->profile_completion_percent = $profile->calculateCompletionPercent();

        return $profile;
    }

    /**
     * Server-side calculation of profile completion percentage (0 - 100%)
     */
    public function calculateCompletionPercent(): int
    {
        $percent = 0;

        if (!empty(trim((string)$this->full_name))) $percent += 10;
        if (!empty(trim((string)$this->phone))) $percent += 10;
        if (!empty(trim((string)$this->university))) $percent += 10;
        if (!empty(trim((string)$this->major))) $percent += 10;
        if (!empty($this->academic_year) && $this->academic_year > 0) $percent += 10;
        if (!empty(trim((string)$this->bio))) $percent += 10;
        if (!empty($this->skill_ids) || !empty(trim((string)$this->skills))) $percent += 15;
        if (!empty($this->available_schedule)) $percent += 15;
        if (!empty(trim((string)$this->cv_url)) || !empty($this->cv_storage_path)) $percent += 10;

        return min(100, $percent);
    }

    /**
     * Private format (for student owner only, includes phone, email, cv_url, active_cv)
     */
    public function toArrayPrivate(): array
    {
        return [
            "id"                         => $this->id,
            "user_id"                    => $this->user_id,
            "full_name"                  => $this->full_name,
            "email"                      => $this->email,
            "phone"                      => $this->phone,
            "date_of_birth"              => $this->date_of_birth,
            "gender"                     => $this->gender,
            "university"                 => $this->university,
            "major"                      => $this->major,
            "academic_year"              => $this->academic_year,
            "bio"                        => $this->bio,
            "location_id"                => $this->location_id,
            "preferred_location"         => $this->preferred_location,
            "preferred_locations"        => $this->preferred_locations,
            "availability_schedule"      => $this->available_schedule,
            "available_schedule"         => $this->available_schedule,
            "skill_ids"                  => $this->skill_ids,
            "skills"                     => $this->skills,
            "work_experience"            => $this->work_experience,
            "education"                  => $this->education,
            "certificates"               => $this->certificates,
            "cv_url"                     => $this->cv_url,
            "active_cv"                  => $this->cv_storage_path ? [
                "file_name"   => $this->cv_original_name,
                "file_size"   => $this->cv_file_size ?? 0,
                "mime_type"   => $this->cv_mime_type ?? "application/pdf",
                "uploaded_at" => $this->cv_uploaded_at,
            ] : null,
            "profile_completion_percent" => $this->calculateCompletionPercent(),
            "created_at"                 => $this->created_at,
            "updated_at"                 => $this->updated_at,
        ];
    }

    /**
     * Public format (for public view - completely strips email, phone, cv_url, private data)
     */
    public function toArrayPublic(): array
    {
        return [
            "id"                         => $this->id,
            "user_id"                    => $this->user_id,
            "full_name"                  => $this->full_name,
            "university"                 => $this->university,
            "major"                      => $this->major,
            "academic_year"              => $this->academic_year,
            "bio"                        => $this->bio,
            "location_id"                => $this->location_id,
            "preferred_location"         => $this->preferred_location,
            "preferred_locations"        => $this->preferred_locations,
            "availability_schedule"      => $this->available_schedule,
            "skills"                     => $this->skills,
            "work_experience"            => $this->work_experience,
            "education"                  => $this->education,
            "certificates"               => $this->certificates,
            "profile_completion_percent" => $this->calculateCompletionPercent(),
            "created_at"                 => $this->created_at,
        ];
    }

    // Getters & Setters
    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->user_id; }
    public function getFullName(): ?string { return $this->full_name; }
    public function getPhone(): ?string { return $this->phone; }
    public function getDateOfBirth(): ?string { return $this->date_of_birth; }
    public function getGender(): ?string { return $this->gender; }
    public function getUniversity(): ?string { return $this->university; }
    public function getMajor(): ?string { return $this->major; }
    public function getAcademicYear(): ?int { return $this->academic_year; }
    public function getBio(): ?string { return $this->bio; }
    public function getLocationId(): ?string { return $this->location_id; }
    public function getPreferredLocation(): ?string { return $this->preferred_location; }
    public function getPreferredLocations(): ?string { return $this->preferred_locations; }
    public function getAvailableSchedule(): ?array { return $this->available_schedule; }
    public function getSkills(): ?string { return $this->skills; }
    public function getSkillIds(): ?array { return $this->skill_ids; }
    public function getWorkExperience(): ?string { return $this->work_experience; }
    public function getEducation(): ?string { return $this->education; }
    public function getCertificates(): ?string { return $this->certificates; }
    public function getCvUrl(): ?string { return $this->cv_url; }
    public function getCvStoragePath(): ?string { return $this->cv_storage_path; }
    public function getCvOriginalName(): ?string { return $this->cv_original_name; }
    public function getCvMimeType(): ?string { return $this->cv_mime_type; }
    public function getCvFileSize(): ?int { return $this->cv_file_size; }
    public function getCvUploadedAt(): ?string { return $this->cv_uploaded_at; }
    public function getProfileCompletionPercent(): int { return $this->profile_completion_percent; }
    public function getEmail(): ?string { return $this->email; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }

    public function setFullName(?string $val): self { $this->full_name = $val; return $this; }
    public function setPhone(?string $val): self { $this->phone = $val; return $this; }
    public function setDateOfBirth(?string $val): self { $this->date_of_birth = $val; return $this; }
    public function setGender(?string $val): self { $this->gender = $val; return $this; }
    public function setUniversity(?string $val): self { $this->university = $val; return $this; }
    public function setMajor(?string $val): self { $this->major = $val; return $this; }
    public function setAcademicYear(?int $val): self { $this->academic_year = $val; return $this; }
    public function setBio(?string $val): self { $this->bio = $val; return $this; }
    public function setLocationId(?string $val): self { $this->location_id = $val; return $this; }
    public function setPreferredLocation(?string $val): self { $this->preferred_location = $val; return $this; }
    public function setPreferredLocations(?string $val): self { $this->preferred_locations = $val; return $this; }
    public function setAvailableSchedule(?array $val): self { $this->available_schedule = $val; return $this; }
    public function setSkills(?string $val): self { $this->skills = $val; return $this; }
    public function setSkillIds(?array $val): self { $this->skill_ids = $val; return $this; }
    public function setWorkExperience(?string $val): self { $this->work_experience = $val; return $this; }
    public function setEducation(?string $val): self { $this->education = $val; return $this; }
    public function setCertificates(?string $val): self { $this->certificates = $val; return $this; }
    public function setCvUrl(?string $val): self { $this->cv_url = $val; return $this; }
    public function setCvStoragePath(?string $val): self { $this->cv_storage_path = $val; return $this; }
    public function setCvOriginalName(?string $val): self { $this->cv_original_name = $val; return $this; }
    public function setCvMimeType(?string $val): self { $this->cv_mime_type = $val; return $this; }
    public function setCvFileSize(?int $val): self { $this->cv_file_size = $val; return $this; }
    public function setCvUploadedAt(?string $val): self { $this->cv_uploaded_at = $val; return $this; }
    public function setEmail(?string $val): self { $this->email = $val; return $this; }
}