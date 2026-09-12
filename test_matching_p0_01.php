<?php

declare(strict_types=1);

/**
 * CV-AI-P0-01 Automated Offline Domain Regression Test Suite
 *
 * Requirements & Acceptance Criteria Tested:
 * 1. Valid CandidateProfileContract is created and serialized correctly.
 * 2. Valid JobRequirementsContract is created and serialized correctly.
 * 3. Valid MatchResultContract is created and serialized correctly.
 * 4. Invalid data states (e.g., 'INVALID_STATE', 'PENDING') are rejected.
 * 5. Invalid enum day of week (e.g., 'someday') and shift (e.g., 'midday') are rejected.
 * 6. Confidence < 0.0 or > 1.0 is rejected deterministically.
 * 7. Score < 0 or > 100 is rejected deterministically.
 * 8. Type mismatch is rejected without dangerous coercion (e.g., string '1.0' for float).
 * 9. Unknown/additional fields are rejected under strict mode.
 * 10. String length limits (e.g., > 255) and array count limits (e.g., > 100) are rejected.
 * 11. Missing-data states (UNKNOWN, NOT_APPLICABLE, NOT_AVAILABLE) are preserved through serialization.
 * 12. PII / protected attributes (full_name, email, phone, dob, gender, exact address, cv_storage_path, employer_note) are strictly rejected.
 * 13. Classification values allowlist enforcement (strictly HIGH_MATCH, GOOD_MATCH, REVIEW_NEEDED, INSUFFICIENT_DATA - no accept/reject).
 * 14. Completely offline execution: Zero database connections, zero network calls, zero Gemini calls.
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use JobMarket\Domain\Matching\Contracts\CandidateProfileContract;
use JobMarket\Domain\Matching\Contracts\JobRequirementsContract;
use JobMarket\Domain\Matching\Contracts\MatchResultContract;
use JobMarket\Domain\Matching\Enums\CriterionName;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\DayOfWeek;
use JobMarket\Domain\Matching\Enums\MatchClassification;
use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JobMarket\Domain\Matching\ValueObjects\AvailabilityValue;
use JobMarket\Domain\Matching\ValueObjects\CandidateLocationValue;
use JobMarket\Domain\Matching\ValueObjects\CriterionEvaluation;
use JobMarket\Domain\Matching\ValueObjects\EducationRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\EducationValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceItem;
use JobMarket\Domain\Matching\ValueObjects\ExperienceRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceValue;
use JobMarket\Domain\Matching\ValueObjects\JobLocationValue;
use JobMarket\Domain\Matching\ValueObjects\MissingDataSection;
use JobMarket\Domain\Matching\ValueObjects\RoleValue;
use JobMarket\Domain\Matching\ValueObjects\SalaryValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleSlot;
use JobMarket\Domain\Matching\ValueObjects\SkillItem;

class MatchingContractsTestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=================================================================\n";
        echo "   CV-AI-P0-01 CANONICAL MATCHING CONTRACTS REGRESSION SUITE   \n";
        echo "=================================================================\n\n";

        $this->testValidCandidateProfileContract();
        $this->testValidJobRequirementsContract();
        $this->testValidMatchResultContract();
        $this->testInvalidDataStateRejection();
        $this->testInvalidEnumDayAndShiftRejection();
        $this->testConfidenceRangeRejection();
        $this->testScoreRangeRejection();
        $this->testTypeMismatchRejectionWithoutCoercion();
        $this->testStrictUnknownFieldRejection();
        $this->testStringAndArrayLimitsRejection();
        $this->testMissingDataStatesPreservedInSerialization();
        $this->testPiiAndProtectedAttributesStrictRejection();
        $this->testMatchClassificationNonHiringSemantics();
        $this->testProjectsDesiredRolesSalaryExpectationNotAvailable();
        $this->testContractAndValueObjectInvariants();

        echo "\n-----------------------------------------------------------------\n";
        echo "Summary: {$this->passed} PASSED, {$this->failed} FAILED.\n";
        echo "-----------------------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $description): void
    {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$description}\n";
        } else {
            $this->failed++;
            echo " [FAIL] {$description}\n";
        }
    }

    /**
     * 1. Valid CandidateProfileContract
     */
    private function testValidCandidateProfileContract(): void
    {
        $payload = [
            'schema_version' => 'candidate-profile.v1',
            'source_type' => 'profile',
            'source_profile_id' => 'prof-001',
            'source_application_id' => 'app-001',
            'skills' => [
                [
                    'canonical_name' => 'giao tiếp khách hàng',
                    'source_skill_id' => 'skill-005',
                    'match_key' => 'giao tiep khach hang',
                    'evidence' => 'student_profiles.skill_ids:skill-005',
                    'confidence' => 1.0,
                    'provenance' => 'structured',
                ],
            ],
            'availability' => [
                'state' => 'AVAILABLE',
                'slots' => [
                    ['day' => 'monday', 'shift' => 'evening'],
                ],
                'preferred_shift_for_application' => 'evening',
                'confidence' => 1.0,
            ],
            'experience' => [
                'state' => 'AVAILABLE',
                'items' => [
                    [
                        'role' => 'thu ngân',
                        'duration_months' => 6,
                        'evidence' => 'Từng làm thu ngân 6 tháng',
                        'confidence' => 0.92,
                        'provenance' => 'semantic_extraction',
                    ],
                ],
            ],
            'education' => [
                'state' => 'AVAILABLE',
                'major' => 'Công nghệ Thông tin',
                'academic_year' => 3,
                'level' => 'current_student',
                'evidence' => 'student_profiles.major/academic_year',
                'confidence' => 1.0,
            ],
            'projects' => [
                'state' => 'NOT_AVAILABLE',
                'items' => [],
            ],
            'certifications' => [
                'state' => 'UNKNOWN',
                'items' => [],
            ],
            'locations' => [
                'state' => 'AVAILABLE',
                'location_ids' => ['loc-001'],
                'names' => ['Hà Nội - Cầu Giấy'],
            ],
            'desired_roles' => [
                'state' => 'NOT_AVAILABLE',
                'items' => [],
            ],
            'salary_expectation' => [
                'state' => 'NOT_AVAILABLE',
                'min' => null,
                'max' => null,
                'type' => null,
                'currency' => null,
            ],
        ];

        $contract = CandidateProfileContract::fromArray($payload, strict: true);
        $array = $contract->toArray();

        $this->assert($array['schema_version'] === 'candidate-profile.v1', 'Candidate contract: schema_version matches');
        $this->assert($array['source_profile_id'] === 'prof-001', 'Candidate contract: source_profile_id matches');
        $this->assert(count($array['skills']) === 1, 'Candidate contract: skills count is 1');
        $this->assert($array['skills'][0]['canonical_name'] === 'giao tiếp khách hàng', 'Candidate contract: skill name matches');
        $this->assert($array['availability']['slots'][0]['day'] === 'monday', 'Candidate contract: slot day is monday');
        $this->assert($array['availability']['slots'][0]['shift'] === 'evening', 'Candidate contract: slot shift is evening');
        $this->assert($array['experience']['items'][0]['duration_months'] === 6, 'Candidate contract: experience duration is 6');
        $this->assert($array['education']['major'] === 'Công nghệ Thông tin', 'Candidate contract: major matches');
        $this->assert($array['projects']['state'] === 'NOT_AVAILABLE', 'Candidate contract: projects is NOT_AVAILABLE');
        $this->assert($array['certifications']['state'] === 'UNKNOWN', 'Candidate contract: certifications is UNKNOWN');
        $this->assert($array['salary_expectation']['state'] === 'NOT_AVAILABLE', 'Candidate contract: salary expectation is NOT_AVAILABLE');

        // JSON serialization check
        $json = json_encode($contract);
        $this->assert(is_string($json) && str_contains($json, 'candidate-profile.v1'), 'Candidate contract: JSON serializes cleanly');
    }

    /**
     * 2. Valid JobRequirementsContract
     */
    private function testValidJobRequirementsContract(): void
    {
        $payload = [
            'schema_version' => 'job-requirement.v1',
            'source_job_id' => 'job-001',
            'role' => [
                'title' => 'Nhân viên thu ngân part-time',
                'category_id' => 'cat-001',
                'category_name' => 'F&B - Nhà hàng / Quán cà phê',
                'semantic_terms' => [
                    [
                        'canonical_name' => 'thu ngân',
                        'evidence' => 'Nhân viên thu ngân part-time',
                        'confidence' => 0.99,
                    ],
                ],
            ],
            'skills' => [
                [
                    'canonical_name' => 'thu ngân & pos',
                    'source_skill_id' => 'skill-002',
                    'importance' => 'required',
                    'evidence' => 'required_skills:skill-002',
                    'confidence' => 1.0,
                    'provenance' => 'structured',
                ],
            ],
            'experience_requirement' => [
                'state' => 'NOT_APPLICABLE',
                'minimum_months' => null,
                'domains' => [],
                'evidence' => 'Không yêu cầu kinh nghiệm',
                'confidence' => 0.98,
            ],
            'education_requirement' => [
                'state' => 'UNKNOWN',
                'levels' => [],
                'majors' => [],
                'evidence' => null,
                'confidence' => 0.0,
            ],
            'schedule' => [
                'state' => 'AVAILABLE',
                'shift_type' => 'evening',
                'slots' => [],
                'minimum_shifts_per_week' => 4,
                'evidence' => 'Đăng ký 4 buổi tối/tuần',
                'confidence' => 0.9,
            ],
            'location' => [
                'state' => 'AVAILABLE',
                'location_id' => 'loc-001',
                'city' => 'Hà Nội',
                'district' => 'Cầu Giấy',
                'work_mode' => 'onsite',
            ],
            'salary' => [
                'state' => 'AVAILABLE',
                'type' => 'hourly',
                'min' => 30000,
                'max' => 38000,
                'currency' => 'VND',
            ],
            'application_state' => [
                'status' => 'published',
                'deadline' => '2026-09-23',
            ],
        ];

        $contract = JobRequirementsContract::fromArray($payload, strict: true);
        $array = $contract->toArray();

        $this->assert($array['schema_version'] === 'job-requirement.v1', 'Job contract: schema_version matches');
        $this->assert($array['source_job_id'] === 'job-001', 'Job contract: source_job_id matches');
        $this->assert($array['role']['title'] === 'Nhân viên thu ngân part-time', 'Job contract: role title matches');
        $this->assert($array['skills'][0]['importance'] === 'required', 'Job contract: skill importance is required');
        $this->assert($array['experience_requirement']['state'] === 'NOT_APPLICABLE', 'Job contract: experience_requirement is NOT_APPLICABLE');
        $this->assert($array['education_requirement']['state'] === 'UNKNOWN', 'Job contract: education_requirement is UNKNOWN');
        $this->assert($array['schedule']['shift_type'] === 'evening', 'Job contract: shift_type is evening');
        $this->assert($array['salary']['min'] === 30000, 'Job contract: salary min is 30000');
        $this->assert($array['salary']['max'] === 38000, 'Job contract: salary max is 38000');
        $this->assert($array['application_state']['status'] === 'published', 'Job contract: application_state status is published');

        // JSON serialization check
        $json = json_encode($contract);
        $this->assert(is_string($json) && str_contains($json, 'job-requirement.v1'), 'Job contract: JSON serializes cleanly');
    }

    /**
     * 3. Valid MatchResultContract
     */
    private function testValidMatchResultContract(): void
    {
        $payload = [
            'schema_version' => 'match-result.v1',
            'candidate_id' => 'app-001',
            'job_id' => 'job-001',
            'overall_score' => 78.5,
            'coverage_percent' => 80.0,
            'classification' => 'GOOD_MATCH',
            'criteria' => [
                [
                    'criterion_name' => 'skills',
                    'state' => 'AVAILABLE',
                    'score' => 80.0,
                    'weight' => 30.0,
                    'confidence' => 1.0,
                    'provenance' => 'structured',
                    'evidence' => 'Match 2 of 2 required skills',
                    'details' => ['matched' => ['thu ngân & pos']],
                ],
                [
                    'criterion_name' => 'availability',
                    'state' => 'AVAILABLE',
                    'score' => 75.0,
                    'weight' => 30.0,
                    'confidence' => 0.95,
                    'provenance' => 'structured',
                    'evidence' => 'Compatible evening shift',
                    'details' => [],
                ],
                [
                    'criterion_name' => 'experience',
                    'state' => 'NOT_APPLICABLE',
                    'score' => null,
                    'weight' => 10.0,
                    'confidence' => 0.98,
                    'provenance' => 'semantic_extraction',
                    'evidence' => 'Job does not require prior experience',
                    'details' => [],
                ],
            ],
            'strengths' => ['Kỹ năng phù hợp với yêu cầu ca làm'],
            'considerations' => ['Cần xác nhận lại lịch làm việc cụ thể'],
            'missing_data' => ['projects' => 'NOT_AVAILABLE', 'salary_expectation' => 'NOT_AVAILABLE'],
            'disclaimer' => MatchResultContract::DEFAULT_DISCLAIMER,
        ];

        $contract = MatchResultContract::fromArray($payload, strict: true);
        $array = $contract->toArray();

        $this->assert($array['overall_score'] === 78.5, 'Match result: overall_score matches');
        $this->assert($array['coverage_percent'] === 80.0, 'Match result: coverage_percent matches');
        $this->assert($array['classification'] === 'GOOD_MATCH', 'Match result: classification is GOOD_MATCH');
        $this->assert(count($array['criteria']) === 3, 'Match result: criteria count is 3');
        $this->assert($array['criteria'][2]['state'] === 'NOT_APPLICABLE', 'Match result: experience is NOT_APPLICABLE');
        $this->assert($array['criteria'][2]['score'] === null, 'Match result: NOT_APPLICABLE score is null');
        $this->assert(str_contains($array['disclaimer'], 'không thay thế đánh giá'), 'Match result: disclaimer contains non-hiring notice');
    }

    /**
     * 4. Invalid data state rejection
     */
    private function testInvalidDataStateRejection(): void
    {
        $caught = false;
        try {
            AvailabilityValue::fromArray([
                'state' => 'INVALID_STATE',
                'slots' => [],
            ]);
        } catch (MatchingContractValidationException $e) {
            $caught = true;
        }
        $this->assert($caught, 'Invalid data state "INVALID_STATE" was rejected');

        $caught2 = false;
        try {
            ExperienceValue::fromArray([
                'state' => 'PENDING',
                'items' => [],
            ]);
        } catch (MatchingContractValidationException $e) {
            $caught2 = true;
        }
        $this->assert($caught2, 'Invalid data state "PENDING" was rejected');
    }

    /**
     * 5. Invalid enum day and shift rejection
     */
    private function testInvalidEnumDayAndShiftRejection(): void
    {
        $caughtDay = false;
        try {
            ScheduleSlot::fromArray([
                'day' => 'someday',
                'shift' => 'evening',
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtDay = true;
        }
        $this->assert($caughtDay, 'Invalid day "someday" was rejected');

        $caughtShift = false;
        try {
            ScheduleSlot::fromArray([
                'day' => 'monday',
                'shift' => 'midday',
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtShift = true;
        }
        $this->assert($caughtShift, 'Invalid shift "midday" was rejected');
    }

    /**
     * 6. Confidence range rejection (< 0.0 or > 1.0)
     */
    private function testConfidenceRangeRejection(): void
    {
        $caughtLow = false;
        try {
            SkillItem::fromArray([
                'canonical_name' => 'PHP',
                'confidence' => -0.1,
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtLow = true;
        }
        $this->assert($caughtLow, 'Confidence -0.1 was rejected');

        $caughtHigh = false;
        try {
            SkillItem::fromArray([
                'canonical_name' => 'PHP',
                'confidence' => 1.05,
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtHigh = true;
        }
        $this->assert($caughtHigh, 'Confidence 1.05 was rejected');
    }

    /**
     * 7. Score range rejection (< 0 or > 100)
     */
    private function testScoreRangeRejection(): void
    {
        $caughtLow = false;
        try {
            CriterionEvaluation::fromArray([
                'criterion_name' => 'skills',
                'state' => 'AVAILABLE',
                'score' => -1.0,
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtLow = true;
        }
        $this->assert($caughtLow, 'Score -1.0 was rejected');

        $caughtHigh = false;
        try {
            CriterionEvaluation::fromArray([
                'criterion_name' => 'skills',
                'state' => 'AVAILABLE',
                'score' => 100.5,
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtHigh = true;
        }
        $this->assert($caughtHigh, 'Score 100.5 was rejected');

        $caughtNonAvailableScore = false;
        try {
            CriterionEvaluation::fromArray([
                'criterion_name' => 'skills',
                'state' => 'UNKNOWN',
                'score' => 50.0,
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtNonAvailableScore = true;
        }
        $this->assert($caughtNonAvailableScore, 'Score provided for UNKNOWN state was rejected');
    }

    /**
     * 8. Type mismatch rejection without coercion
     */
    private function testTypeMismatchRejectionWithoutCoercion(): void
    {
        $caughtStringConfidence = false;
        try {
            SkillItem::fromArray([
                'canonical_name' => 'PHP',
                'confidence' => '1.0', // string instead of float
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtStringConfidence = true;
        }
        $this->assert($caughtStringConfidence, 'String "1.0" rejected for confidence (no coercion)');

        $caughtStringScore = false;
        try {
            CriterionEvaluation::fromArray([
                'criterion_name' => 'skills',
                'state' => 'AVAILABLE',
                'score' => '85', // string instead of numeric
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtStringScore = true;
        }
        $this->assert($caughtStringScore, 'String "85" rejected for score (no coercion)');
    }

    /**
     * 9. Strict unknown field rejection
     */
    private function testStrictUnknownFieldRejection(): void
    {
        $caughtCandidate = false;
        try {
            CandidateProfileContract::fromArray([
                'schema_version' => 'candidate-profile.v1',
                'unknown_field_foo' => 'bar',
            ], strict: true);
        } catch (MatchingContractValidationException $e) {
            $caughtCandidate = true;
        }
        $this->assert($caughtCandidate, 'Unknown field in CandidateProfileContract was rejected in strict mode');

        $caughtJob = false;
        try {
            JobRequirementsContract::fromArray([
                'schema_version' => 'job-requirement.v1',
                'arbitrary_extra_key' => 123,
            ], strict: true);
        } catch (MatchingContractValidationException $e) {
            $caughtJob = true;
        }
        $this->assert($caughtJob, 'Unknown field in JobRequirementsContract was rejected in strict mode');
    }

    /**
     * 10. String and array limits rejection
     */
    private function testStringAndArrayLimitsRejection(): void
    {
        $caughtLongString = false;
        try {
            SkillItem::fromArray([
                'canonical_name' => str_repeat('A', 256),
            ]);
        } catch (MatchingContractValidationException $e) {
            $caughtLongString = true;
        }
        $this->assert($caughtLongString, 'String exceeding 255 characters was rejected');

        $caughtExcessiveSkills = false;
        try {
            $skills = [];
            for ($i = 0; $i < 101; $i++) {
                $skills[] = ['canonical_name' => "Skill {$i}"];
            }
            CandidateProfileContract::fromArray([
                'skills' => $skills,
            ], strict: false);
        } catch (MatchingContractValidationException $e) {
            $caughtExcessiveSkills = true;
        }
        $this->assert($caughtExcessiveSkills, 'Array exceeding 100 items was rejected');
    }

    /**
     * 11. Missing-data states preserved in serialization
     */
    private function testMissingDataStatesPreservedInSerialization(): void
    {
        $candidate = new CandidateProfileContract(
            projects: MissingDataSection::notAvailable(),
            certifications: MissingDataSection::unknown(),
            desiredRoles: MissingDataSection::notAvailable(),
            salaryExpectation: new SalaryValue(state: DataState::NOT_AVAILABLE)
        );

        $arr = $candidate->toArray();
        $this->assert($arr['projects']['state'] === 'NOT_AVAILABLE', 'Serialization preserves projects as NOT_AVAILABLE');
        $this->assert($arr['certifications']['state'] === 'UNKNOWN', 'Serialization preserves certifications as UNKNOWN');
        $this->assert($arr['desired_roles']['state'] === 'NOT_AVAILABLE', 'Serialization preserves desired_roles as NOT_AVAILABLE');
        $this->assert($arr['salary_expectation']['state'] === 'NOT_AVAILABLE', 'Serialization preserves salary_expectation as NOT_AVAILABLE');

        $job = new JobRequirementsContract(
            experienceRequirement: new ExperienceRequirementValue(state: DataState::NOT_APPLICABLE),
            educationRequirement: new EducationRequirementValue(state: DataState::UNKNOWN)
        );

        $jArr = $job->toArray();
        $this->assert($jArr['experience_requirement']['state'] === 'NOT_APPLICABLE', 'Serialization preserves experience_requirement as NOT_APPLICABLE');
        $this->assert($jArr['education_requirement']['state'] === 'UNKNOWN', 'Serialization preserves education_requirement as UNKNOWN');
    }

    /**
     * 12. PII and protected attributes strict rejection
     */
    private function testPiiAndProtectedAttributesStrictRejection(): void
    {
        $piiExamples = [
            'full_name' => 'Nguyễn Văn A',
            'email' => 'student@test.com',
            'phone' => '0912345678',
            'date_of_birth' => '2003-01-01',
            'dob' => '2003-01-01',
            'gender' => 'male',
            'address' => 'Số 123 Đường Láng',
            'cv_storage_path' => 'storage/app/cvs/file.pdf',
            'employer_note' => 'Ghi chú nội bộ bí mật',
        ];

        foreach ($piiExamples as $key => $val) {
            $caught = false;
            try {
                CandidateProfileContract::fromArray([$key => $val], strict: false);
            } catch (MatchingContractValidationException $e) {
                $caught = true;
            }
            $this->assert($caught, "PII field '{$key}' was strictly rejected in CandidateProfileContract");
        }

        // Test nested PII rejection
        $caughtNested = false;
        try {
            CandidateProfileContract::fromArray([
                'skills' => [
                    [
                        'canonical_name' => 'PHP',
                        'email' => 'nested@leaked.com',
                    ],
                ],
            ], strict: false);
        } catch (MatchingContractValidationException $e) {
            $caughtNested = true;
        }
        $this->assert($caughtNested, 'Nested PII inside skill item was strictly detected and rejected');
    }

    /**
     * 13. Match classification non-hiring semantics
     */
    private function testMatchClassificationNonHiringSemantics(): void
    {
        $validCases = [
            'HIGH_MATCH',
            'GOOD_MATCH',
            'REVIEW_NEEDED',
            'INSUFFICIENT_DATA',
        ];

        foreach ($validCases as $case) {
            $contract = MatchResultContract::fromArray(['classification' => $case], strict: false);
            $this->assert($contract->classification->value === $case, "Valid classification '{$case}' accepted");
        }

        // Prohibited hiring-decision semantics: ACCEPT, REJECT, HIRED, REJECTED
        $hiringKeywords = ['ACCEPT', 'REJECT', 'HIRED', 'REJECTED', 'ELIGIBLE', 'INELIGIBLE'];
        foreach ($hiringKeywords as $kw) {
            $caught = false;
            try {
                MatchResultContract::fromArray(['classification' => $kw], strict: false);
            } catch (MatchingContractValidationException $e) {
                $caught = true;
            }
            $this->assert($caught, "Hiring-decision keyword '{$kw}' strictly rejected from classification");
        }
    }

    /**
     * 14. Projects, desired roles and salary expectation explicitly represented as NOT_AVAILABLE
     */
    private function testProjectsDesiredRolesSalaryExpectationNotAvailable(): void
    {
        $profile = CandidateProfileContract::fromArray([], strict: false);
        $arr = $profile->toArray();

        $this->assert(
            $arr['projects']['state'] === DataState::NOT_AVAILABLE->value,
            'Default CandidateProfileContract represents projects as NOT_AVAILABLE'
        );
        $this->assert(
            $arr['desired_roles']['state'] === DataState::NOT_AVAILABLE->value,
            'Default CandidateProfileContract represents desired_roles as NOT_AVAILABLE'
        );
        $this->assert(
            $arr['salary_expectation']['state'] === DataState::NOT_AVAILABLE->value,
            'Default CandidateProfileContract represents salary_expectation as NOT_AVAILABLE'
        );
    }

    /**
     * 15. Invariants: reject AVAILABLE with empty collections, reject non-AVAILABLE with non-null scores, reject invalid schema_version
     */
    private function testContractAndValueObjectInvariants(): void
    {
        // Invariant 1: Reject AVAILABLE with empty collections
        $caughtExp = false;
        try {
            new ExperienceValue(state: DataState::AVAILABLE, items: []);
        } catch (MatchingContractValidationException $e) {
            $caughtExp = true;
        }
        $this->assert($caughtExp, 'Invariant: ExperienceValue rejects AVAILABLE with empty items');

        $caughtLoc = false;
        try {
            new CandidateLocationValue(state: DataState::AVAILABLE, locationIds: [], names: []);
        } catch (MatchingContractValidationException $e) {
            $caughtLoc = true;
        }
        $this->assert($caughtLoc, 'Invariant: CandidateLocationValue rejects AVAILABLE with empty locationIds and names');

        $caughtAvail = false;
        try {
            new AvailabilityValue(state: DataState::AVAILABLE, slots: [], preferredShiftForApplication: null);
        } catch (MatchingContractValidationException $e) {
            $caughtAvail = true;
        }
        $this->assert($caughtAvail, 'Invariant: AvailabilityValue rejects AVAILABLE with empty slots and no preferred shift');

        $caughtSection = false;
        try {
            new MissingDataSection(state: DataState::AVAILABLE, items: []);
        } catch (MatchingContractValidationException $e) {
            $caughtSection = true;
        }
        $this->assert($caughtSection, 'Invariant: MissingDataSection rejects AVAILABLE with empty items');

        // Invariant 2: CriterionEvaluation score invariants
        $caughtScoreNull = false;
        try {
            new CriterionEvaluation(criterionName: CriterionName::SKILLS, state: DataState::AVAILABLE, score: null);
        } catch (MatchingContractValidationException $e) {
            $caughtScoreNull = true;
        }
        $this->assert($caughtScoreNull, 'Invariant: CriterionEvaluation rejects AVAILABLE with null score');

        $caughtScoreUnknown = false;
        try {
            new CriterionEvaluation(criterionName: CriterionName::SKILLS, state: DataState::UNKNOWN, score: 75.0);
        } catch (MatchingContractValidationException $e) {
            $caughtScoreUnknown = true;
        }
        $this->assert($caughtScoreUnknown, 'Invariant: CriterionEvaluation rejects UNKNOWN with non-null score');

        $caughtScoreNotApp = false;
        try {
            new CriterionEvaluation(criterionName: CriterionName::SKILLS, state: DataState::NOT_APPLICABLE, score: 50.0);
        } catch (MatchingContractValidationException $e) {
            $caughtScoreNotApp = true;
        }
        $this->assert($caughtScoreNotApp, 'Invariant: CriterionEvaluation rejects NOT_APPLICABLE with non-null score');

        // Invariant 3: Contract schema_version and source_type strictness
        $caughtSchemaCandidate = false;
        try {
            new CandidateProfileContract(schemaVersion: 'candidate-profile.v2');
        } catch (MatchingContractValidationException $e) {
            $caughtSchemaCandidate = true;
        }
        $this->assert($caughtSchemaCandidate, 'Invariant: CandidateProfileContract rejects invalid schema_version');

        $caughtSourceType = false;
        try {
            new CandidateProfileContract(sourceType: 'unsupported_source');
        } catch (MatchingContractValidationException $e) {
            $caughtSourceType = true;
        }
        $this->assert($caughtSourceType, 'Invariant: CandidateProfileContract rejects invalid source_type');

        // CandidateProfileContract MVP rejects 'cv' source_type
        $caughtCvSource = false;
        try {
            new CandidateProfileContract(sourceType: 'cv');
        } catch (MatchingContractValidationException $e) {
            $caughtCvSource = true;
        }
        $this->assert($caughtCvSource, 'Invariant: CandidateProfileContract MVP rejects source_type=cv');

        $caughtSchemaJob = false;
        try {
            new JobRequirementsContract(schemaVersion: 'job-requirement.v2');
        } catch (MatchingContractValidationException $e) {
            $caughtSchemaJob = true;
        }
        $this->assert($caughtSchemaJob, 'Invariant: JobRequirementsContract rejects invalid schema_version');

        // MatchResultContract rejects schema_version mismatch
        $caughtSchemaMatch = false;
        try {
            new MatchResultContract(
                schemaVersion: 'match-result.v2',
                candidateId: 'cand-001',
                jobId: 'job-001',
                overallScore: 85.0,
                coveragePercent: 80.0,
                classification: MatchClassification::HIGH_MATCH
            );
        } catch (MatchingContractValidationException $e) {
            $caughtSchemaMatch = true;
        }
        $this->assert($caughtSchemaMatch, 'Invariant: MatchResultContract rejects invalid schema_version');

        // Invariant 4: Required string rejection of empty string and whitespace-only
        $caughtEmptySkill = false;
        try {
            new SkillItem(canonicalName: '');
        } catch (MatchingContractValidationException $e) {
            $caughtEmptySkill = true;
        }
        $this->assert($caughtEmptySkill, 'Invariant: SkillItem rejects empty string canonicalName');

        $caughtWhitespaceSkill = false;
        try {
            new SkillItem(canonicalName: "   \t \n  ");
        } catch (MatchingContractValidationException $e) {
            $caughtWhitespaceSkill = true;
        }
        $this->assert($caughtWhitespaceSkill, 'Invariant: SkillItem rejects whitespace-only canonicalName');

        $caughtEmptyRole = false;
        try {
            new ExperienceItem(role: '');
        } catch (MatchingContractValidationException $e) {
            $caughtEmptyRole = true;
        }
        $this->assert($caughtEmptyRole, 'Invariant: ExperienceItem rejects empty string role');

        $caughtWhitespaceRole = false;
        try {
            new ExperienceItem(role: "   ");
        } catch (MatchingContractValidationException $e) {
            $caughtWhitespaceRole = true;
        }
        $this->assert($caughtWhitespaceRole, 'Invariant: ExperienceItem rejects whitespace-only role');

        $caughtEmptyTitle = false;
        try {
            new RoleValue(title: '');
        } catch (MatchingContractValidationException $e) {
            $caughtEmptyTitle = true;
        }
        $this->assert($caughtEmptyTitle, 'Invariant: RoleValue rejects empty string title');

        $caughtWhitespaceTitle = false;
        try {
            new RoleValue(title: "   ");
        } catch (MatchingContractValidationException $e) {
            $caughtWhitespaceTitle = true;
        }
        $this->assert($caughtWhitespaceTitle, 'Invariant: RoleValue rejects whitespace-only title');

        // Invariant 5: Missing job requirement sections default cleanly to UNKNOWN
        $defaultJob = JobRequirementsContract::fromArray([]);
        $this->assert($defaultJob->experienceRequirement->state === DataState::UNKNOWN, 'Default JobRequirementsContract: experience defaults to UNKNOWN');
        $this->assert($defaultJob->schedule->state === DataState::UNKNOWN, 'Default JobRequirementsContract: schedule defaults to UNKNOWN');
        $this->assert($defaultJob->location->state === DataState::UNKNOWN, 'Default JobRequirementsContract: location defaults to UNKNOWN');
        $this->assert($defaultJob->salary->state === DataState::UNKNOWN, 'Default JobRequirementsContract: salary defaults to UNKNOWN');
        $this->assert($defaultJob->educationRequirement->state === DataState::UNKNOWN, 'Default JobRequirementsContract: education defaults to UNKNOWN');

        // Invariant 6: ValueObject state / data invariants
        // ExperienceRequirementValue
        $caughtExpAvail = false;
        try {
            new ExperienceRequirementValue(state: DataState::AVAILABLE, minimumMonths: null, domains: [], evidence: null);
        } catch (MatchingContractValidationException $e) {
            $caughtExpAvail = true;
        }
        $this->assert($caughtExpAvail, 'Invariant: ExperienceRequirementValue rejects AVAILABLE with empty data');

        $caughtExpNotApp = false;
        try {
            new ExperienceRequirementValue(state: DataState::NOT_APPLICABLE, minimumMonths: 12);
        } catch (MatchingContractValidationException $e) {
            $caughtExpNotApp = true;
        }
        $this->assert($caughtExpNotApp, 'Invariant: ExperienceRequirementValue rejects NOT_APPLICABLE with contradictory minimumMonths');

        // ScheduleRequirementValue
        $caughtSchedAvail = false;
        try {
            new ScheduleRequirementValue(state: DataState::AVAILABLE, shiftType: null, slots: [], evidence: null);
        } catch (MatchingContractValidationException $e) {
            $caughtSchedAvail = true;
        }
        $this->assert($caughtSchedAvail, 'Invariant: ScheduleRequirementValue rejects AVAILABLE with empty shift and slots');

        $caughtSchedNotApp = false;
        try {
            new ScheduleRequirementValue(state: DataState::NOT_APPLICABLE, shiftType: ShiftType::MORNING);
        } catch (MatchingContractValidationException $e) {
            $caughtSchedNotApp = true;
        }
        $this->assert($caughtSchedNotApp, 'Invariant: ScheduleRequirementValue rejects NOT_APPLICABLE with contradictory shiftType');

        // JobLocationValue
        $caughtLocAvail = false;
        try {
            new JobLocationValue(state: DataState::AVAILABLE, locationId: null, city: null, district: null, workMode: null);
        } catch (MatchingContractValidationException $e) {
            $caughtLocAvail = true;
        }
        $this->assert($caughtLocAvail, 'Invariant: JobLocationValue rejects AVAILABLE with no location data');

        $caughtLocNotApp = false;
        try {
            new JobLocationValue(state: DataState::NOT_APPLICABLE, city: 'Hà Nội');
        } catch (MatchingContractValidationException $e) {
            $caughtLocNotApp = true;
        }
        $this->assert($caughtLocNotApp, 'Invariant: JobLocationValue rejects NOT_APPLICABLE with contradictory city');

        // SalaryValue
        $caughtSalAvail = false;
        try {
            new SalaryValue(state: DataState::AVAILABLE, min: null, max: null, type: null);
        } catch (MatchingContractValidationException $e) {
            $caughtSalAvail = true;
        }
        $this->assert($caughtSalAvail, 'Invariant: SalaryValue rejects AVAILABLE with no salary bounds or type');

        $caughtSalNotApp = false;
        try {
            new SalaryValue(state: DataState::NOT_APPLICABLE, min: 10000000);
        } catch (MatchingContractValidationException $e) {
            $caughtSalNotApp = true;
        }
        $this->assert($caughtSalNotApp, 'Invariant: SalaryValue rejects NOT_APPLICABLE with contradictory min');

        // EducationRequirementValue
        $caughtEduAvail = false;
        try {
            new EducationRequirementValue(state: DataState::AVAILABLE, levels: [], majors: [], evidence: null);
        } catch (MatchingContractValidationException $e) {
            $caughtEduAvail = true;
        }
        $this->assert($caughtEduAvail, 'Invariant: EducationRequirementValue rejects AVAILABLE with no levels, majors, or evidence');

        $caughtEduNotApp = false;
        try {
            new EducationRequirementValue(state: DataState::NOT_APPLICABLE, levels: ['Đại học']);
        } catch (MatchingContractValidationException $e) {
            $caughtEduNotApp = true;
        }
        $this->assert($caughtEduNotApp, 'Invariant: EducationRequirementValue rejects NOT_APPLICABLE with contradictory levels');
    }
}

$suite = new MatchingContractsTestSuite();
$suite->run();
