<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Adapters;

use JobMarket\Domain\Matching\Contracts\JobRequirementsContract;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Services\Normalizers\LocationNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\ScheduleNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Domain\Matching\ValueObjects\EducationRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\RoleValue;
use JobMarket\Domain\Matching\ValueObjects\SalaryValue;

final class JobRequirementsAdapter
{
    private PiiRedactor $piiRedactor;

    /**
     * @param array<string, string> $categoryDictionary id => category_name map
     */
    public function __construct(
        private SkillNormalizer $skillNormalizer,
        private ScheduleNormalizer $scheduleNormalizer,
        private LocationNormalizer $locationNormalizer,
        private array $categoryDictionary = [],
        ?PiiRedactor $piiRedactor = null
    ) {
        $this->piiRedactor = $piiRedactor ?? new PiiRedactor();
    }

    /**
     * Adapts raw job record into canonical JobRequirementsContract.
     * Strictly omits exact street address, company private notes, and contact credentials.
     *
     * @param array<string, mixed> $job Raw row from jobs table
     */
    public function adapt(array $job): JobRequirementsContract
    {
        $jobId = isset($job['id']) ? (string)$job['id'] : null;

        // 1. Role
        $rawTitle = !empty($job['title']) ? trim((string)$job['title']) : 'Vị trí part-time';
        $title = $this->piiRedactor->redact($rawTitle) ?? 'Vị trí part-time';
        $categoryId = !empty($job['category_id']) ? (string)$job['category_id'] : null;
        $categoryName = null;
        if ($categoryId !== null && isset($this->categoryDictionary[$categoryId])) {
            $categoryName = $this->categoryDictionary[$categoryId];
        } elseif (!empty($job['category'])) {
            $categoryName = trim((string)$job['category']);
        }

        $semanticTerms = [
            [
                'canonical_name' => $title,
                'evidence' => "jobs.title:{$title}",
                'confidence' => 1.0,
            ],
        ];

        $role = new RoleValue(
            title: $title,
            categoryId: $categoryId,
            categoryName: $categoryName,
            semanticTerms: $semanticTerms
        );

        // 2. Skills
        $rawRequiredSkills = $job['required_skills'] ?? null;
        if (is_string($rawRequiredSkills)) {
            $rawRequiredSkills = $this->piiRedactor->redact($rawRequiredSkills);
        }
        $skills = $this->skillNormalizer->normalizeJobSkills($rawRequiredSkills);

        // 3. Experience Requirement
        $cleanDesc = $this->piiRedactor->redact((string)($job['description'] ?? ''));
        $cleanReq = $this->piiRedactor->redact((string)($job['requirements'] ?? ''));
        $requirementsText = mb_strtolower(($cleanReq ?? '') . ' ' . ($cleanDesc ?? ''));
        $noExpKeywords = [
            'không yêu cầu kinh nghiệm',
            'khong yeu cau kinh nghiem',
            'chưa có kinh nghiệm sẽ được đào tạo',
            'se duoc dao tao',
            'không cần kinh nghiệm',
            'khong can kinh nghiem',
            'không đòi hỏi kinh nghiệm',
        ];

        $isNoExpRequired = false;
        foreach ($noExpKeywords as $kw) {
            if (str_contains($requirementsText, $kw)) {
                $isNoExpRequired = true;
                break;
            }
        }

        if ($isNoExpRequired) {
            $expRequirement = new ExperienceRequirementValue(
                state: DataState::NOT_APPLICABLE,
                minimumMonths: null,
                domains: [],
                evidence: 'Tin tuyển dụng nêu rõ không yêu cầu kinh nghiệm',
                confidence: 0.95
            );
        } else {
            $expRequirement = new ExperienceRequirementValue(
                state: DataState::UNKNOWN,
                minimumMonths: null,
                domains: [],
                evidence: null,
                confidence: 0.0
            );
        }

        // 4. Education Requirement (No structured requirement in database -> UNKNOWN)
        $educationRequirement = new EducationRequirementValue(
            state: DataState::UNKNOWN,
            levels: [],
            majors: [],
            evidence: null,
            confidence: 0.0
        );

        // 5. Schedule Requirement
        $shiftType = !empty($job['shift_type']) ? (string)$job['shift_type'] : null;
        $shiftInfo = !empty($job['shift_information']) ? (string)$job['shift_information'] : null;
        if ($shiftInfo !== null) {
            $shiftInfo = $this->piiRedactor->redact($shiftInfo);
        }
        $workingSchedule = !empty($job['working_schedule']) ? (string)$job['working_schedule'] : null;
        if ($workingSchedule !== null) {
            $workingSchedule = $this->piiRedactor->redact($workingSchedule);
        }
        $schedule = $this->scheduleNormalizer->normalizeJobSchedule($shiftType, $shiftInfo, $workingSchedule);

        // 6. Location (Coarse city/district/IDs only, NO exact address)
        $locationId = !empty($job['location_id']) ? (string)$job['location_id'] : null;
        $city = !empty($job['city']) ? (string)$job['city'] : null;
        $district = !empty($job['district']) ? (string)$job['district'] : null;
        $workMode = !empty($job['work_mode']) ? (string)$job['work_mode'] : 'onsite';
        $location = $this->locationNormalizer->normalizeJobLocation($locationId, $city, $district, $workMode);

        // 7. Salary
        $salaryType = !empty($job['salary_type']) ? (string)$job['salary_type'] : null;
        $salaryMin = isset($job['salary_min']) && is_numeric($job['salary_min']) ? (float)$job['salary_min'] : null;
        $salaryMax = isset($job['salary_max']) && is_numeric($job['salary_max']) ? (float)$job['salary_max'] : null;
        $currency = !empty($job['currency']) ? (string)$job['currency'] : 'VND';

        $hasSalary = ($salaryMin !== null || $salaryMax !== null || $salaryType !== null);
        $salary = new SalaryValue(
            state: $hasSalary ? DataState::AVAILABLE : DataState::UNKNOWN,
            type: $salaryType,
            min: $salaryMin,
            max: $salaryMax,
            currency: $currency
        );

        // 8. Application State
        $status = !empty($job['status']) ? (string)$job['status'] : 'published';
        $deadline = !empty($job['application_deadline']) ? (string)$job['application_deadline'] : null;

        return new JobRequirementsContract(
            schemaVersion: JobRequirementsContract::SCHEMA_VERSION,
            sourceJobId: $jobId,
            role: $role,
            skills: $skills,
            experienceRequirement: $expRequirement,
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
}
