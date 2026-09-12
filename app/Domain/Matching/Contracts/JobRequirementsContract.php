<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Contracts;

use JobMarket\Domain\Matching\Support\ValidationGuard;
use JobMarket\Domain\Matching\ValueObjects\EducationRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\JobLocationValue;
use JobMarket\Domain\Matching\ValueObjects\RoleValue;
use JobMarket\Domain\Matching\ValueObjects\SalaryValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\SkillItem;
use JsonSerializable;

final readonly class JobRequirementsContract implements JsonSerializable
{
    public const SCHEMA_VERSION = 'job-requirement.v1';

    public const ALLOWED_KEYS = [
        'schema_version',
        'source_job_id',
        'role',
        'skills',
        'experience_requirement',
        'education_requirement',
        'schedule',
        'location',
        'salary',
        'application_state',
    ];

    /**
     * @param list<SkillItem> $skills
     * @param array{status: string, deadline: ?string} $applicationState
     */
    public function __construct(
        public string $schemaVersion = self::SCHEMA_VERSION,
        public ?string $sourceJobId = null,
        public ?RoleValue $role = null,
        public array $skills = [],
        public ?ExperienceRequirementValue $experienceRequirement = null,
        public ?EducationRequirementValue $educationRequirement = null,
        public ?ScheduleRequirementValue $schedule = null,
        public ?JobLocationValue $location = null,
        public ?SalaryValue $salary = null,
        public array $applicationState = ['status' => 'published', 'deadline' => null]
    ) {
        if ($this->schemaVersion !== self::SCHEMA_VERSION) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['schema_version' => "Schema version '{$this->schemaVersion}' không hợp lệ. Yêu cầu: '" . self::SCHEMA_VERSION . "'."],
                "Schema version không hợp lệ"
            );
        }
        ValidationGuard::assertStringLength($this->schemaVersion, 'schema_version', 32);
        ValidationGuard::assertStringLength($this->sourceJobId, 'source_job_id', 64, true);
        ValidationGuard::assertArrayLimit($this->skills, 'skills', 100);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'job_requirements');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'job_requirements');
        }

        $schemaVersion = ValidationGuard::assertStringLength(
            $data['schema_version'] ?? self::SCHEMA_VERSION,
            'schema_version',
            32
        );
        $sourceJobId = ValidationGuard::assertStringLength(
            $data['source_job_id'] ?? null,
            'source_job_id',
            64,
            true
        );

        $role = null;
        if (isset($data['role'])) {
            $role = $data['role'] instanceof RoleValue
                ? $data['role']
                : RoleValue::fromArray($data['role'], $strict);
        }

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

        $experienceRequirement = null;
        if (isset($data['experience_requirement'])) {
            $experienceRequirement = $data['experience_requirement'] instanceof ExperienceRequirementValue
                ? $data['experience_requirement']
                : ExperienceRequirementValue::fromArray($data['experience_requirement'], $strict);
        } else {
            $experienceRequirement = new ExperienceRequirementValue();
        }

        $educationRequirement = null;
        if (isset($data['education_requirement'])) {
            $educationRequirement = $data['education_requirement'] instanceof EducationRequirementValue
                ? $data['education_requirement']
                : EducationRequirementValue::fromArray($data['education_requirement'], $strict);
        } else {
            $educationRequirement = new EducationRequirementValue();
        }

        $schedule = null;
        if (isset($data['schedule'])) {
            $schedule = $data['schedule'] instanceof ScheduleRequirementValue
                ? $data['schedule']
                : ScheduleRequirementValue::fromArray($data['schedule'], $strict);
        } else {
            $schedule = new ScheduleRequirementValue();
        }

        $location = null;
        if (isset($data['location'])) {
            $location = $data['location'] instanceof JobLocationValue
                ? $data['location']
                : JobLocationValue::fromArray($data['location'], $strict);
        } else {
            $location = new JobLocationValue();
        }

        $salary = null;
        if (isset($data['salary'])) {
            $salary = $data['salary'] instanceof SalaryValue
                ? $data['salary']
                : SalaryValue::fromArray($data['salary'], $strict);
        } else {
            $salary = new SalaryValue();
        }

        $appStateRaw = $data['application_state'] ?? ['status' => 'published', 'deadline' => null];
        if (!is_array($appStateRaw)) {
            ValidationGuard::assertArrayLimit($appStateRaw, 'application_state');
        }
        if ($strict) {
            ValidationGuard::assertStrictKeys($appStateRaw, ['status', 'deadline'], 'application_state');
        }
        $status = ValidationGuard::assertStringLength($appStateRaw['status'] ?? 'published', 'status', 50);
        $deadline = ValidationGuard::assertStringLength($appStateRaw['deadline'] ?? null, 'deadline', 50, true);

        return new self(
            schemaVersion: $schemaVersion,
            sourceJobId: $sourceJobId,
            role: $role,
            skills: $skills,
            experienceRequirement: $experienceRequirement,
            educationRequirement: $educationRequirement,
            schedule: $schedule,
            location: $location,
            salary: $salary,
            applicationState: [
                'status' => $status,
                'deadline' => $deadline,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'source_job_id' => $this->sourceJobId,
            'role' => $this->role?->toArray(),
            'skills' => array_map(fn(SkillItem $s) => $s->toArray(), $this->skills),
            'experience_requirement' => ($this->experienceRequirement ?? new ExperienceRequirementValue())->toArray(),
            'education_requirement' => ($this->educationRequirement ?? new EducationRequirementValue())->toArray(),
            'schedule' => ($this->schedule ?? new ScheduleRequirementValue())->toArray(),
            'location' => ($this->location ?? new JobLocationValue())->toArray(),
            'salary' => ($this->salary ?? new SalaryValue())->toArray(),
            'application_state' => $this->applicationState,
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
