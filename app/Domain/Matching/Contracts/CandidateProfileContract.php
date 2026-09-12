<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Contracts;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JobMarket\Domain\Matching\ValueObjects\AvailabilityValue;
use JobMarket\Domain\Matching\ValueObjects\CandidateLocationValue;
use JobMarket\Domain\Matching\ValueObjects\EducationValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceValue;
use JobMarket\Domain\Matching\ValueObjects\MissingDataSection;
use JobMarket\Domain\Matching\ValueObjects\SalaryValue;
use JobMarket\Domain\Matching\ValueObjects\SkillItem;
use JsonSerializable;

final readonly class CandidateProfileContract implements JsonSerializable
{
    public const SCHEMA_VERSION = 'candidate-profile.v1';
    public const SOURCE_TYPE = 'profile';

    public const ALLOWED_KEYS = [
        'schema_version',
        'source_type',
        'source_profile_id',
        'source_application_id',
        'skills',
        'availability',
        'experience',
        'education',
        'projects',
        'certifications',
        'locations',
        'desired_roles',
        'salary_expectation',
    ];

    /**
     * @param list<SkillItem> $skills
     */
    public function __construct(
        public string $schemaVersion = self::SCHEMA_VERSION,
        public string $sourceType = self::SOURCE_TYPE,
        public ?string $sourceProfileId = null,
        public ?string $sourceApplicationId = null,
        public array $skills = [],
        public ?AvailabilityValue $availability = null,
        public ?ExperienceValue $experience = null,
        public ?EducationValue $education = null,
        public ?MissingDataSection $projects = null,
        public ?MissingDataSection $certifications = null,
        public ?CandidateLocationValue $locations = null,
        public ?MissingDataSection $desiredRoles = null,
        public ?SalaryValue $salaryExpectation = null
    ) {
        if ($this->schemaVersion !== self::SCHEMA_VERSION) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['schema_version' => "Schema version '{$this->schemaVersion}' không hợp lệ. Yêu cầu: '" . self::SCHEMA_VERSION . "'."],
                "Schema version không hợp lệ"
            );
        }
        if ($this->sourceType !== self::SOURCE_TYPE) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['source_type' => "Source type '{$this->sourceType}' không hợp lệ. Trong MVP chỉ hỗ trợ: " . self::SOURCE_TYPE . "."],
                "Source type không hợp lệ"
            );
        }
        ValidationGuard::assertStringLength($this->schemaVersion, 'schema_version', 32);
        ValidationGuard::assertStringLength($this->sourceType, 'source_type', 32);
        ValidationGuard::assertStringLength($this->sourceProfileId, 'source_profile_id', 64, true);
        ValidationGuard::assertStringLength($this->sourceApplicationId, 'source_application_id', 64, true);
        ValidationGuard::assertArrayLimit($this->skills, 'skills', 100);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'candidate_profile');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'candidate_profile');
        }

        $schemaVersion = ValidationGuard::assertStringLength(
            $data['schema_version'] ?? self::SCHEMA_VERSION,
            'schema_version',
            32
        );
        $sourceType = ValidationGuard::assertStringLength(
            $data['source_type'] ?? self::SOURCE_TYPE,
            'source_type',
            32
        );
        $sourceProfileId = ValidationGuard::assertStringLength(
            $data['source_profile_id'] ?? null,
            'source_profile_id',
            64,
            true
        );
        $sourceApplicationId = ValidationGuard::assertStringLength(
            $data['source_application_id'] ?? null,
            'source_application_id',
            64,
            true
        );

        $rawSkills = ValidationGuard::assertArrayLimit($data['skills'] ?? [], 'skills', 100);
        $skills = [];
        foreach ($rawSkills as $skill) {
            if ($skill instanceof SkillItem) {
                $skills[] = $skill;
            } elseif (is_array($skill)) {
                $skills[] = SkillItem::fromArray($skill, $strict);
            } else {
                ValidationGuard::assertArrayLimit($skill, 'skill_item');
            }
        }

        $availability = null;
        if (isset($data['availability'])) {
            $availability = $data['availability'] instanceof AvailabilityValue
                ? $data['availability']
                : AvailabilityValue::fromArray($data['availability'], $strict);
        } else {
            $availability = new AvailabilityValue(state: DataState::UNKNOWN);
        }

        $experience = null;
        if (isset($data['experience'])) {
            $experience = $data['experience'] instanceof ExperienceValue
                ? $data['experience']
                : ExperienceValue::fromArray($data['experience'], $strict);
        } else {
            $experience = new ExperienceValue(state: DataState::UNKNOWN);
        }

        $education = null;
        if (isset($data['education'])) {
            $education = $data['education'] instanceof EducationValue
                ? $data['education']
                : EducationValue::fromArray($data['education'], $strict);
        } else {
            $education = new EducationValue(state: DataState::UNKNOWN);
        }

        $projects = null;
        if (isset($data['projects'])) {
            $projects = $data['projects'] instanceof MissingDataSection
                ? $data['projects']
                : MissingDataSection::fromArray($data['projects'], $strict);
        } else {
            $projects = MissingDataSection::notAvailable();
        }

        $certifications = null;
        if (isset($data['certifications'])) {
            $certifications = $data['certifications'] instanceof MissingDataSection
                ? $data['certifications']
                : MissingDataSection::fromArray($data['certifications'], $strict);
        } else {
            $certifications = MissingDataSection::unknown();
        }

        $locations = null;
        if (isset($data['locations'])) {
            $locations = $data['locations'] instanceof CandidateLocationValue
                ? $data['locations']
                : CandidateLocationValue::fromArray($data['locations'], $strict);
        } else {
            $locations = new CandidateLocationValue(state: DataState::UNKNOWN);
        }

        $desiredRoles = null;
        if (isset($data['desired_roles'])) {
            $desiredRoles = $data['desired_roles'] instanceof MissingDataSection
                ? $data['desired_roles']
                : MissingDataSection::fromArray($data['desired_roles'], $strict);
        } else {
            $desiredRoles = MissingDataSection::notAvailable();
        }

        $salaryExpectation = null;
        if (isset($data['salary_expectation'])) {
            $salaryExpectation = $data['salary_expectation'] instanceof SalaryValue
                ? $data['salary_expectation']
                : SalaryValue::fromArray($data['salary_expectation'], $strict);
        } else {
            $salaryExpectation = new SalaryValue(state: DataState::NOT_AVAILABLE);
        }

        return new self(
            schemaVersion: $schemaVersion,
            sourceType: $sourceType,
            sourceProfileId: $sourceProfileId,
            sourceApplicationId: $sourceApplicationId,
            skills: $skills,
            availability: $availability,
            experience: $experience,
            education: $education,
            projects: $projects,
            certifications: $certifications,
            locations: $locations,
            desiredRoles: $desiredRoles,
            salaryExpectation: $salaryExpectation
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'source_type' => $this->sourceType,
            'source_profile_id' => $this->sourceProfileId,
            'source_application_id' => $this->sourceApplicationId,
            'skills' => array_map(fn(SkillItem $s) => $s->toArray(), $this->skills),
            'availability' => ($this->availability ?? new AvailabilityValue(state: DataState::UNKNOWN))->toArray(),
            'experience' => ($this->experience ?? new ExperienceValue(state: DataState::UNKNOWN))->toArray(),
            'education' => ($this->education ?? new EducationValue(state: DataState::UNKNOWN))->toArray(),
            'projects' => ($this->projects ?? MissingDataSection::notAvailable())->toArray(),
            'certifications' => ($this->certifications ?? MissingDataSection::unknown())->toArray(),
            'locations' => ($this->locations ?? new CandidateLocationValue(state: DataState::UNKNOWN))->toArray(),
            'desired_roles' => ($this->desiredRoles ?? MissingDataSection::notAvailable())->toArray(),
            'salary_expectation' => ($this->salaryExpectation ?? new SalaryValue(state: DataState::NOT_AVAILABLE))->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
