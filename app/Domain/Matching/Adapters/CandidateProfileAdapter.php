<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Adapters;

use JobMarket\Domain\Matching\Contracts\CandidateProfileContract;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Services\Normalizers\LocationNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\ScheduleNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Domain\Matching\ValueObjects\EducationValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceItem;
use JobMarket\Domain\Matching\ValueObjects\ExperienceValue;
use JobMarket\Domain\Matching\ValueObjects\MissingDataSection;
use JobMarket\Domain\Matching\ValueObjects\SalaryValue;

final class CandidateProfileAdapter
{
    private PiiRedactor $piiRedactor;

    public function __construct(
        private SkillNormalizer $skillNormalizer,
        private ScheduleNormalizer $scheduleNormalizer,
        private LocationNormalizer $locationNormalizer,
        ?PiiRedactor $piiRedactor = null
    ) {
        $this->piiRedactor = $piiRedactor ?? new PiiRedactor();
    }

    /**
     * Adapts raw student profile record and application preferred shift
     * into a canonical redacted CandidateProfileContract.
     *
     * @param array<string, mixed> $studentProfile Raw row from student_profiles table
     * @param ?string $applicationPreferredShift Preferred shift chosen by student during apply
     * @param ?string $sourceApplicationId Application ID reference
     */
    public function adapt(
        array $studentProfile,
        ?string $applicationPreferredShift = null,
        ?string $sourceApplicationId = null
    ): CandidateProfileContract {
        // Redaction is enforced by construction: DOB is converted to age only and never leaves this adapter.
        $profileId = isset($studentProfile['id']) ? (string)$studentProfile['id'] : null;

        $ageYears = null;
        if (!empty($studentProfile['date_of_birth'])) {
            try {
                $birthDate = new \DateTimeImmutable((string)$studentProfile['date_of_birth']);
                $today = new \DateTimeImmutable('today');
                if ($birthDate <= $today) {
                    $calculatedAge = $birthDate->diff($today)->y;
                    if ($calculatedAge >= 0 && $calculatedAge <= 120) {
                        $ageYears = $calculatedAge;
                    }
                }
            } catch (\Throwable) {
                $ageYears = null;
            }
        }

        $knownName = !empty($studentProfile['full_name'])
            ? (string)$studentProfile['full_name']
            : (!empty($studentProfile['name']) ? (string)$studentProfile['name'] : null);

        // 1. Skills
        $skillIds = $studentProfile['skill_ids'] ?? null;
        $skillsText = isset($studentProfile['skills']) ? (string)$studentProfile['skills'] : null;
        if ($skillsText !== null) {
            $skillsText = $this->piiRedactor->redact($skillsText, $knownName);
        }
        $skills = $this->skillNormalizer->normalizeCandidateSkills($skillIds, $skillsText);

        // 2. Availability & Schedule
        $rawSchedule = $studentProfile['available_schedule'] ?? null;
        $availability = $this->scheduleNormalizer->normalizeCandidateSchedule(
            $rawSchedule,
            $applicationPreferredShift
        );

        // 3. Education
        $major = !empty($studentProfile['major']) ? trim((string)$studentProfile['major']) : null;
        if ($major !== null) {
            $major = $this->piiRedactor->redact($major, $knownName);
        }
        $year = isset($studentProfile['academic_year']) && is_numeric($studentProfile['academic_year'])
            ? (int)$studentProfile['academic_year']
            : null;
        $eduText = !empty($studentProfile['education']) ? trim((string)$studentProfile['education']) : null;
        if ($eduText !== null) {
            $eduText = $this->piiRedactor->redact($eduText, $knownName);
        }

        $eduState = ($major !== null || $year !== null || $eduText !== null)
            ? DataState::AVAILABLE
            : DataState::UNKNOWN;

        $education = new EducationValue(
            state: $eduState,
            major: $major,
            academicYear: ($year !== null && $year >= 1 && $year <= 10) ? $year : null,
            level: 'current_student',
            evidence: $eduText ?? ($major ? "student_profiles.major:{$major}" : null),
            confidence: $major !== null ? 1.0 : 0.8
        );

        // 4. Experience
        $workExp = !empty($studentProfile['work_experience']) ? trim((string)$studentProfile['work_experience']) : null;
        if ($workExp !== null) {
            $workExp = $this->piiRedactor->redact($workExp, $knownName);
        }
        $experienceItems = [];
        if ($workExp !== null) {
            $cleanRole = PiiRedactor::stripPlaceholdersAndContact($workExp);
            if ($cleanRole !== '' && mb_strlen($cleanRole) >= 2 && !PiiRedactor::containsPlaceholder($cleanRole)) {
                $experienceItems[] = new ExperienceItem(
                    role: mb_substr($cleanRole, 0, 255),
                    durationMonths: null,
                    evidence: mb_substr($workExp, 0, 1000),
                    confidence: 0.85,
                    provenance: Provenance::SEMANTIC_EXTRACTION
                );
                $expState = DataState::AVAILABLE;
            } else {
                $expState = DataState::UNKNOWN;
            }
        } else {
            $expState = DataState::UNKNOWN;
        }
        $experience = new ExperienceValue(
            state: $expState,
            items: $experienceItems
        );

        // 5. Projects (Not available in current database schema)
        $projects = MissingDataSection::notAvailable();

        // 6. Certifications
        $certText = !empty($studentProfile['certificates']) ? trim((string)$studentProfile['certificates']) : null;
        if ($certText !== null) {
            $certText = $this->piiRedactor->redact($certText, $knownName);
        }
        $certItems = [];
        if ($certText !== null) {
            $cleanCert = PiiRedactor::stripPlaceholdersAndContact($certText);
            if ($cleanCert !== '' && mb_strlen($cleanCert) >= 2 && !PiiRedactor::containsPlaceholder($cleanCert)) {
                $certItems[] = $cleanCert;
            }
        }
        $certifications = !empty($certItems)
            ? new MissingDataSection(state: DataState::AVAILABLE, items: $certItems)
            : MissingDataSection::unknown();

        // 7. Locations (Coarse city/district/IDs only, NO exact address)
        $locationId = !empty($studentProfile['location_id']) ? (string)$studentProfile['location_id'] : null;
        $prefLoc = !empty($studentProfile['preferred_location']) ? (string)$studentProfile['preferred_location'] : null;
        if ($prefLoc !== null) {
            $prefLoc = $this->piiRedactor->redact($prefLoc, $knownName);
        }
        $prefLocs = $studentProfile['preferred_locations'] ?? null;
        $locations = $this->locationNormalizer->normalizeCandidateLocations(
            $locationId,
            $prefLoc,
            $prefLocs
        );

        // 8. Desired Roles (Not available in current schema)
        $desiredRoles = MissingDataSection::notAvailable();

        // 9. Salary Expectation (Not available in candidate profile schema)
        $salaryExpectation = new SalaryValue(state: DataState::NOT_AVAILABLE);

        return new CandidateProfileContract(
            schemaVersion: CandidateProfileContract::SCHEMA_VERSION,
            sourceType: CandidateProfileContract::SOURCE_TYPE,
            sourceProfileId: $profileId,
            sourceApplicationId: $sourceApplicationId,
            skills: $skills,
            availability: $availability,
            experience: $experience,
            education: $education,
            projects: $projects,
            certifications: $certifications,
            locations: $locations,
            desiredRoles: $desiredRoles,
            salaryExpectation: $salaryExpectation,
            ageYears: $ageYears
        );
    }
}
