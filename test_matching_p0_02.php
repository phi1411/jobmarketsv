<?php

declare(strict_types=1);

/**
 * CV-AI-P0-02 Automated Offline Regression Test Suite:
 * Canonical Adapters, Normalizers, Redaction, and HMAC Hashing
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use JobMarket\Domain\Matching\Adapters\CandidateProfileAdapter;
use JobMarket\Domain\Matching\Adapters\JobRequirementsAdapter;
use JobMarket\Domain\Matching\Contracts\CandidateProfileContract;
use JobMarket\Domain\Matching\Contracts\JobRequirementsContract;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\DayOfWeek;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Services\CanonicalHashService;
use JobMarket\Domain\Matching\Services\Normalizers\LocationNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\ScheduleNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\ValueObjects\SkillItem;

class MatchingAdaptersTestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=================================================================\n";
        echo "   CV-AI-P0-02 ADAPTERS, NORMALIZATION & HASHING TEST SUITE      \n";
        echo "=================================================================\n\n";

        $this->testSkillNormalizerCandidate();
        $this->testSkillNormalizerJob();
        $this->testScheduleNormalizerMatrix();
        $this->testScheduleNormalizerLegacy();
        $this->testScheduleNormalizerEmptyAndMalformed();
        $this->testLocationNormalizer();
        $this->testCandidateProfileAdapter();
        $this->testJobRequirementsAdapter();
        $this->testCanonicalHashServiceStability();
        $this->testCanonicalHashServiceDifferentMatchingFields();
        $this->testCanonicalHashServicePiiExclusionFromHash();
        $this->testCanonicalHashServiceKeyValidation();
        $this->testPermutationStability();
        $this->testFreeTextPiiRedactionAndNoFabrication();

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

    private function testSkillNormalizerCandidate(): void
    {
        $dictionary = [
            'skill-001' => 'Giao tiếp khách hàng',
            'skill-002' => 'Thu ngân & POS',
            'skill-003' => 'Pha chế cà phê',
        ];

        $normalizer = new SkillNormalizer($dictionary);

        // 1. Valid skill_ids JSON
        $skills = $normalizer->normalizeCandidateSkills(json_encode(['skill-001', 'skill-002']));
        $this->assert(count($skills) === 2, 'SkillNormalizer: 2 valid candidate skill IDs resolved');
        $this->assert($skills[0]->canonicalName === 'Giao tiếp khách hàng', 'SkillNormalizer: skill-001 name matches');
        $this->assert($skills[0]->sourceSkillId === 'skill-001', 'SkillNormalizer: source ID preserved');

        // 2. Unknown skill ID is NOT treated as valid canonical skill
        $skillsUnknown = $normalizer->normalizeCandidateSkills(json_encode(['skill-999_unknown']));
        $this->assert(empty($skillsUnknown), 'SkillNormalizer: unknown skill ID omitted from canonical list');

        // 3. Free text fallback
        $skillsText = $normalizer->normalizeCandidateSkills(null, 'Pha chế cà phê, Tiếng Anh giao tiếp');
        $this->assert(count($skillsText) === 2, 'SkillNormalizer: free-text skills parsed into items');
        $this->assert($skillsText[0]->sourceSkillId === 'skill-003', 'SkillNormalizer: free-text matches known dictionary ID');
        $this->assert($skillsText[1]->sourceSkillId === null, 'SkillNormalizer: unmapped text gets null sourceSkillId');
    }

    private function testSkillNormalizerJob(): void
    {
        $dictionary = [
            'skill-002' => 'Thu ngân & POS',
            'skill-004' => 'Phục vụ bàn',
        ];
        $normalizer = new SkillNormalizer($dictionary);

        // 1. JSON IDs
        $jSkills1 = $normalizer->normalizeJobSkills(json_encode(['skill-002']));
        $this->assert(count($jSkills1) === 1, 'Job skills: JSON ID resolved');
        $this->assert($jSkills1[0]->canonicalName === 'Thu ngân & POS', 'Job skills: canonical name matches');
        $this->assert($jSkills1[0]->importance === 'required', 'Job skills: importance is required');

        // 2. Comma-separated names
        $jSkills2 = $normalizer->normalizeJobSkills('Phục vụ bàn, Bán hàng');
        $this->assert(count($jSkills2) === 2, 'Job skills: comma-separated names resolved');
        $this->assert($jSkills2[0]->sourceSkillId === 'skill-004', 'Job skills: Phục vụ bàn maps to skill-004');
    }

    private function testScheduleNormalizerMatrix(): void
    {
        $normalizer = new ScheduleNormalizer();
        $matrix = [
            'monday' => ['evening'],
            'saturday' => ['morning', 'afternoon'],
        ];

        $avail = $normalizer->normalizeCandidateSchedule(json_encode($matrix), 'evening');
        $this->assert($avail->state === DataState::AVAILABLE, 'Matrix schedule: state is AVAILABLE');
        $this->assert(count($avail->slots) === 3, 'Matrix schedule: 3 slots generated');
        $this->assert($avail->preferredShiftForApplication === ShiftType::EVENING, 'Matrix schedule: preferred_shift parsed');
    }

    private function testScheduleNormalizerLegacy(): void
    {
        $normalizer = new ScheduleNormalizer();
        $legacy = [
            'shifts' => ['evening', 'weekend'],
        ];

        $avail = $normalizer->normalizeCandidateSchedule(json_encode($legacy), null);
        $this->assert($avail->state === DataState::AVAILABLE, 'Legacy schedule: state is AVAILABLE');
        $this->assert(count($avail->slots) === 7, 'Legacy schedule: expanded into 7 weekday/weekend slots');
    }

    private function testScheduleNormalizerEmptyAndMalformed(): void
    {
        $normalizer = new ScheduleNormalizer();

        // Empty schedule -> UNKNOWN, does not assume busy
        $availEmpty = $normalizer->normalizeCandidateSchedule('', null);
        $this->assert($availEmpty->state === DataState::UNKNOWN, 'Empty schedule returns state UNKNOWN');
        $this->assert(empty($availEmpty->slots), 'Empty schedule has empty slots');

        // Malformed JSON -> UNKNOWN, no crash
        $availMalformed = $normalizer->normalizeCandidateSchedule('{malformed_json:', null);
        $this->assert($availMalformed->state === DataState::UNKNOWN, 'Malformed schedule JSON returns UNKNOWN without crashing');

        // Non-allowlist day/shift
        $weird = ['someday' => ['invalid_shift']];
        $availWeird = $normalizer->normalizeCandidateSchedule(json_encode($weird), null);
        $this->assert($availWeird->state === DataState::UNKNOWN, 'Invalid days/shifts omitted resulting in UNKNOWN');
    }

    private function testLocationNormalizer(): void
    {
        $locations = [
            'loc-001' => 'Hà Nội - Cầu Giấy',
            'loc-002' => 'Hà Nội - Đống Đa',
        ];
        $normalizer = new LocationNormalizer($locations);

        // Candidate location
        $candLoc = $normalizer->normalizeCandidateLocations('loc-001', 'Hà Nội - Cầu Giấy', json_encode(['loc-002']));
        $this->assert($candLoc->state === DataState::AVAILABLE, 'Candidate location state is AVAILABLE');
        $this->assert(count($candLoc->locationIds) === 2, 'Candidate location has 2 IDs');

        // Job location (removes exact street address)
        $jobLoc = $normalizer->normalizeJobLocation('loc-001', 'Hà Nội', 'Cầu Giấy', 'onsite');
        $this->assert($jobLoc->state === DataState::AVAILABLE, 'Job location state is AVAILABLE');
        $this->assert($jobLoc->city === 'Hà Nội', 'Job city matches');
        $this->assert($jobLoc->district === 'Cầu Giấy', 'Job district matches');

        // Remote work mode
        $remoteLoc = $normalizer->normalizeJobLocation(null, null, null, 'remote');
        $this->assert($remoteLoc->state === DataState::AVAILABLE, 'Remote work mode is AVAILABLE');
        $this->assert($remoteLoc->workMode === 'remote', 'Remote work mode matches');
    }

    private function testCandidateProfileAdapter(): void
    {
        $skills = ['skill-001' => 'Giao tiếp khách hàng'];
        $locations = ['loc-001' => 'Hà Nội - Cầu Giấy'];

        $skillNorm = new SkillNormalizer($skills);
        $schedNorm = new ScheduleNormalizer();
        $locNorm = new LocationNormalizer($locations);

        $adapter = new CandidateProfileAdapter($skillNorm, $schedNorm, $locNorm);

        $rawStudentProfile = [
            'id' => 'prof-001',
            'user_id' => 'user-100',
            // PII fields present in raw DB row:
            'full_name' => 'Nguyễn Văn Test',
            'phone' => '0987654321',
            'email' => 'test@student.com',
            'date_of_birth' => '2004-05-10',
            'gender' => 'male',
            'address' => 'Số 99 Phố Trần Duy Hưng',
            'cv_storage_path' => 'storage/app/cvs/confidential.pdf',
            'cv_original_name' => 'secret_cv.pdf',
            // Safe fields:
            'major' => 'Kinh tế Quốc dân',
            'academic_year' => 2,
            'work_experience' => 'Từng làm thu ngân 4 tháng',
            'education' => 'Sinh viên năm 2',
            'certificates' => 'TOEIC 750',
            'location_id' => 'loc-001',
            'available_schedule' => json_encode(['monday' => ['evening']]),
            'skill_ids' => json_encode(['skill-001']),
        ];

        $contract = $adapter->adapt($rawStudentProfile, 'evening', 'app-555');
        $arr = $contract->toArray();

        // Assert contract created successfully
        $this->assert($arr['source_profile_id'] === 'prof-001', 'Adapter: profile ID mapped');
        $this->assert($arr['source_application_id'] === 'app-555', 'Adapter: application ID mapped');
        $this->assert(count($arr['skills']) === 1, 'Adapter: skills adapted');
        $this->assert($arr['availability']['preferred_shift_for_application'] === 'evening', 'Adapter: preferred_shift adapted');
        $this->assert($arr['education']['major'] === 'Kinh tế Quốc dân', 'Adapter: major adapted');
        $this->assert($arr['projects']['state'] === 'NOT_AVAILABLE', 'Adapter: projects is NOT_AVAILABLE');
        $this->assert($arr['salary_expectation']['state'] === 'NOT_AVAILABLE', 'Adapter: salary_expectation is NOT_AVAILABLE');

        // STRICT PII REDACTION ASSERTION:
        $json = json_encode($arr);
        $this->assert(!str_contains($json, 'Nguyễn Văn Test'), 'Redaction: full_name stripped');
        $this->assert(!str_contains($json, '0987654321'), 'Redaction: phone stripped');
        $this->assert(!str_contains($json, 'test@student.com'), 'Redaction: email stripped');
        $this->assert(!str_contains($json, '2004-05-10'), 'Redaction: date_of_birth stripped');
        $this->assert(!str_contains($json, 'Trần Duy Hưng'), 'Redaction: exact street address stripped');
        $this->assert(!str_contains($json, 'confidential.pdf'), 'Redaction: cv_storage_path stripped');
    }

    private function testJobRequirementsAdapter(): void
    {
        $skills = ['skill-002' => 'Thu ngân & POS'];
        $categories = ['cat-001' => 'F&B - Quán cà phê'];
        $locations = ['loc-001' => 'Hà Nội - Cầu Giấy'];

        $skillNorm = new SkillNormalizer($skills);
        $schedNorm = new ScheduleNormalizer();
        $locNorm = new LocationNormalizer($locations);

        $adapter = new JobRequirementsAdapter($skillNorm, $schedNorm, $locNorm, $categories);

        $rawJob = [
            'id' => 'job-101',
            'title' => 'Nhân viên thu ngân',
            'category_id' => 'cat-001',
            'required_skills' => json_encode(['skill-002']),
            'shift_type' => 'evening',
            'location_id' => 'loc-001',
            'city' => 'Hà Nội',
            'district' => 'Cầu Giấy',
            'address' => 'Số 12 Chùa Láng (Private Address)',
            'salary_type' => 'hourly',
            'salary_min' => 25000,
            'salary_max' => 30000,
            'requirements' => 'Không yêu cầu kinh nghiệm, sẽ được đào tạo khi nhận việc',
            'description' => 'Mô tả công việc thu ngân',
        ];

        $contract = $adapter->adapt($rawJob);
        $arr = $contract->toArray();

        $this->assert($arr['source_job_id'] === 'job-101', 'JobAdapter: source_job_id matches');
        $this->assert($arr['role']['title'] === 'Nhân viên thu ngân', 'JobAdapter: role title matches');
        $this->assert($arr['role']['category_name'] === 'F&B - Quán cà phê', 'JobAdapter: category resolved');
        $this->assert($arr['experience_requirement']['state'] === 'NOT_APPLICABLE', 'JobAdapter: detected no experience required');
        $this->assert($arr['schedule']['shift_type'] === 'evening', 'JobAdapter: shift_type matches');
        $this->assert($arr['salary']['min'] == 25000, 'JobAdapter: salary min matches');

        // Check exact address stripped
        $json = json_encode($arr);
        $this->assert(!str_contains($json, 'Chùa Láng'), 'JobAdapter: exact street address stripped');
    }

    private function testCanonicalHashServiceStability(): void
    {
        $hasher = new CanonicalHashService('test_secret_key_at_least_32_characters_long_123456');

        $candidate1 = new CandidateProfileContract(
            sourceProfileId: 'prof-1',
            skills: [new SkillItem(canonicalName: 'PHP', sourceSkillId: 'skill-1')]
        );

        $candidate2 = new CandidateProfileContract(
            sourceProfileId: 'prof-1',
            skills: [new SkillItem(canonicalName: 'PHP', sourceSkillId: 'skill-1')]
        );

        $hash1 = $hasher->computeCandidateHash($candidate1);
        $hash2 = $hasher->computeCandidateHash($candidate2);

        $this->assert(strlen($hash1) === 64, 'Candidate hash is 64 characters (SHA256 hex)');
        $this->assert($hash1 === $hash2, 'Same logical input produces identical HMAC hash');
    }

    private function testCanonicalHashServiceDifferentMatchingFields(): void
    {
        $hasher = new CanonicalHashService('test_secret_key_at_least_32_characters_long_123456');

        $candidateA = new CandidateProfileContract(
            sourceProfileId: 'prof-1',
            skills: [new SkillItem(canonicalName: 'PHP', sourceSkillId: 'skill-1')]
        );

        $candidateB = new CandidateProfileContract(
            sourceProfileId: 'prof-1',
            skills: [new SkillItem(canonicalName: 'Java', sourceSkillId: 'skill-2')]
        );

        $hashA = $hasher->computeCandidateHash($candidateA);
        $hashB = $hasher->computeCandidateHash($candidateB);

        $this->assert($hashA !== $hashB, 'Different matching skills produce different hashes');
    }

    private function testCanonicalHashServicePiiExclusionFromHash(): void
    {
        $skills = ['skill-001' => 'Giao tiếp khách hàng'];
        $locations = ['loc-001' => 'Hà Nội'];
        $skillNorm = new SkillNormalizer($skills);
        $schedNorm = new ScheduleNormalizer();
        $locNorm = new LocationNormalizer($locations);
        $adapter = new CandidateProfileAdapter($skillNorm, $schedNorm, $locNorm);
        $hasher = new CanonicalHashService('test_secret_key_at_least_32_characters_long_123456');

        $profileA = [
            'id' => 'prof-001',
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0911111111',
            'email' => 'a@gmail.com',
            'skill_ids' => json_encode(['skill-001']),
        ];

        $profileB = [
            'id' => 'prof-001',
            'full_name' => 'Trần Thị B',      // Different PII name
            'phone' => '0999999999',          // Different PII phone
            'email' => 'b_changed@gmail.com', // Different PII email
            'skill_ids' => json_encode(['skill-001']), // Same skills
        ];

        $contractA = $adapter->adapt($profileA);
        $contractB = $adapter->adapt($profileB);

        $hashA = $hasher->computeCandidateHash($contractA);
        $hashB = $hasher->computeCandidateHash($contractB);

        $this->assert($hashA === $hashB, 'PII-only changes DO NOT alter matching hash');
    }

    private function testCanonicalHashServiceKeyValidation(): void
    {
        // 1. Weak key < 32 chars throws InvalidArgumentException
        $caughtWeak = false;
        try {
            new CanonicalHashService('short_key_12345');
        } catch (\InvalidArgumentException $e) {
            $caughtWeak = true;
        }
        $this->assert($caughtWeak, 'CanonicalHashService rejects key under 32 characters');

        // 2. Valid key >= 32 chars succeeds
        $validKey = str_repeat('a', 32);
        $hasher = new CanonicalHashService($validKey);
        $this->assert($hasher instanceof CanonicalHashService, 'CanonicalHashService accepts 32+ char key');
    }

    private function testPermutationStability(): void
    {
        $hasher = new CanonicalHashService('test_secret_key_at_least_32_characters_long_123456');

        // Permutation of skills
        $cand1 = new CandidateProfileContract(
            skills: [
                new SkillItem(canonicalName: 'PHP', matchKey: 'php'),
                new SkillItem(canonicalName: 'Java', matchKey: 'java'),
                new SkillItem(canonicalName: 'Docker', matchKey: 'docker'),
            ]
        );
        $cand2 = new CandidateProfileContract(
            skills: [
                new SkillItem(canonicalName: 'Docker', matchKey: 'docker'),
                new SkillItem(canonicalName: 'PHP', matchKey: 'php'),
                new SkillItem(canonicalName: 'Java', matchKey: 'java'),
            ]
        );

        $hash1 = $hasher->computeCandidateHash($cand1);
        $hash2 = $hasher->computeCandidateHash($cand2);
        $this->assert($hash1 === $hash2, 'Permutation stability: Skills order does not alter candidate hash');

        // Permutation of slots in availability
        $candSlot1 = new CandidateProfileContract(
            availability: new \JobMarket\Domain\Matching\ValueObjects\AvailabilityValue(
                state: \JobMarket\Domain\Matching\Enums\DataState::AVAILABLE,
                slots: [
                    new \JobMarket\Domain\Matching\ValueObjects\ScheduleSlot(\JobMarket\Domain\Matching\Enums\DayOfWeek::MONDAY, \JobMarket\Domain\Matching\Enums\ShiftType::MORNING),
                    new \JobMarket\Domain\Matching\ValueObjects\ScheduleSlot(\JobMarket\Domain\Matching\Enums\DayOfWeek::FRIDAY, \JobMarket\Domain\Matching\Enums\ShiftType::EVENING),
                ]
            )
        );
        $candSlot2 = new CandidateProfileContract(
            availability: new \JobMarket\Domain\Matching\ValueObjects\AvailabilityValue(
                state: \JobMarket\Domain\Matching\Enums\DataState::AVAILABLE,
                slots: [
                    new \JobMarket\Domain\Matching\ValueObjects\ScheduleSlot(\JobMarket\Domain\Matching\Enums\DayOfWeek::FRIDAY, \JobMarket\Domain\Matching\Enums\ShiftType::EVENING),
                    new \JobMarket\Domain\Matching\ValueObjects\ScheduleSlot(\JobMarket\Domain\Matching\Enums\DayOfWeek::MONDAY, \JobMarket\Domain\Matching\Enums\ShiftType::MORNING),
                ]
            )
        );
        $hashSlot1 = $hasher->computeCandidateHash($candSlot1);
        $hashSlot2 = $hasher->computeCandidateHash($candSlot2);
        $this->assert($hashSlot1 === $hashSlot2, 'Permutation stability: Schedule slots order does not alter candidate hash');

        // Permutation of locations
        $candLoc1 = new CandidateProfileContract(
            locations: new \JobMarket\Domain\Matching\ValueObjects\CandidateLocationValue(
                state: \JobMarket\Domain\Matching\Enums\DataState::AVAILABLE,
                locationIds: ['loc-2', 'loc-1'],
                names: ['Hà Nội - Đống Đa', 'Hà Nội - Cầu Giấy']
            )
        );
        $candLoc2 = new CandidateProfileContract(
            locations: new \JobMarket\Domain\Matching\ValueObjects\CandidateLocationValue(
                state: \JobMarket\Domain\Matching\Enums\DataState::AVAILABLE,
                locationIds: ['loc-1', 'loc-2'],
                names: ['Hà Nội - Cầu Giấy', 'Hà Nội - Đống Đa']
            )
        );
        $hashLoc1 = $hasher->computeCandidateHash($candLoc1);
        $hashLoc2 = $hasher->computeCandidateHash($candLoc2);
        $this->assert($hashLoc1 === $hashLoc2, 'Permutation stability: Locations order does not alter candidate hash');
    }

    private function testFreeTextPiiRedactionAndNoFabrication(): void
    {
        $skillNorm = new SkillNormalizer([]);
        $schedNorm = new ScheduleNormalizer();
        $locNorm = new LocationNormalizer([]);
        $adapter = new CandidateProfileAdapter($skillNorm, $schedNorm, $locNorm);

        // Case: Candidate profile has phone number in work_experience: "Gọi cho tôi 0912345678"
        // After redaction, no useful experience remains -> state becomes UNKNOWN (NOT fabricating fake experience item)
        $profile = [
            'id' => 'prof-001',
            'full_name' => 'Nguyễn Văn A',
            'work_experience' => 'Liên hệ 0912345678',
        ];

        $contract = $adapter->adapt($profile);
        $this->assert(
            $contract->experience->state === \JobMarket\Domain\Matching\Enums\DataState::UNKNOWN,
            'Free-text PII: Experience containing solely PII becomes UNKNOWN (no fabricated item)'
        );
        $this->assert(empty($contract->experience->items), 'Free-text PII: No experience items fabricated');

        // Case 1: Candidate free-text skills: "PHP, 0912345678" -> only "PHP"
        $skills1 = $skillNorm->normalizeCandidateSkills(null, 'PHP, 0912345678');
        $this->assert(count($skills1) === 1, 'PII in skills: "PHP, 0912345678" produces only 1 skill');
        $this->assert($skills1[0]->canonicalName === 'PHP', 'PII in skills: canonicalName is "PHP" without phone placeholder');

        // Case 2: Candidate free-text skills: "SQL, student@example.com" -> only "SQL"
        $skills2 = $skillNorm->normalizeCandidateSkills(null, 'SQL, student@example.com');
        $this->assert(count($skills2) === 1, 'PII in skills: "SQL, student@example.com" produces only 1 skill');
        $this->assert($skills2[0]->canonicalName === 'SQL', 'PII in skills: canonicalName is "SQL" without email placeholder');

        // Case 3: Experience text solely contact boilerplate -> UNKNOWN state, empty items
        $profileBoilerplate = [
            'id' => 'prof-002',
            'full_name' => 'Nguyễn Văn A',
            'work_experience' => 'Liên hệ qua số 0912345678 hoặc email test@example.com',
        ];
        $contractBoilerplate = $adapter->adapt($profileBoilerplate);
        $this->assert($contractBoilerplate->experience->state === \JobMarket\Domain\Matching\Enums\DataState::UNKNOWN, 'Solely contact boilerplate in exp -> UNKNOWN');
        $this->assert(empty($contractBoilerplate->experience->items), 'Solely contact boilerplate in exp -> no items');

        // Case 4: Experience text with valid content + phone -> clean role
        $profileValidWithPhone = [
            'id' => 'prof-003',
            'full_name' => 'Trần Văn B',
            'work_experience' => 'Nhân viên pha chế cà phê, gọi 0987654321',
        ];
        $contractValidWithPhone = $adapter->adapt($profileValidWithPhone);
        $this->assert($contractValidWithPhone->experience->state === \JobMarket\Domain\Matching\Enums\DataState::AVAILABLE, 'Valid exp with phone -> AVAILABLE');
        $this->assert(count($contractValidWithPhone->experience->items) === 1, 'Valid exp with phone -> 1 item');
        $this->assert(!str_contains($contractValidWithPhone->experience->items[0]->role, '0987654321'), 'Exp role does not contain raw phone');
        $this->assert(!str_contains($contractValidWithPhone->experience->items[0]->role, '[REDACTED'), 'Exp role does not contain [REDACTED placeholder');

        // Case 5: Employment date range (e.g. 01/2021 - 06/2023) is preserved and not redacted as DOB
        $redactor = new \JobMarket\Domain\Matching\Services\PiiRedactor();
        $dateText = 'Kinh nghiệm từ 01/2021 - 06/2023 tại công ty ABC';
        $redactedDateText = $redactor->redact($dateText, 'Trần Văn B');
        $this->assert(str_contains($redactedDateText, '01/2021') && str_contains($redactedDateText, '06/2023'), 'Employment date range is preserved (not redacted as DOB)');

        // Case 5b: Explicit DOB context IS redacted
        $dobText = 'Ngày sinh: 15/05/2000, tham gia từ 2021';
        $redactedDob = $redactor->redact($dobText, 'Trần Văn B');
        $this->assert(!str_contains($redactedDob, '15/05/2000'), 'Explicit DOB context is redacted');
        $this->assert(str_contains($redactedDob, '[REDACTED_DOB]'), 'DOB replaced with [REDACTED_DOB]');

        // Case 6: Known-name replacement avoids substring collision (e.g., "An" in "Angular" or "Management")
        $angularText = 'Thành thạo Angular và Project Management';
        $redactedAngular = $redactor->redact($angularText, 'An');
        $this->assert(str_contains($redactedAngular, 'Angular'), 'Name "An" does not collide with "Angular"');
        $this->assert(str_contains($redactedAngular, 'Management'), 'Name "An" does not collide with "Management"');

        // Standalone "An" IS redacted
        $nameText = 'Tôi tên là An, lập trình viên';
        $redactedName = $redactor->redact($nameText, 'An');
        $this->assert(!str_contains($redactedName, 'là An'), 'Standalone name "An" is redacted');
        $this->assert(str_contains($redactedName, '[REDACTED_NAME]'), 'Standalone name "An" replaced by [REDACTED_NAME]');
    }
}

$suite = new MatchingAdaptersTestSuite();
$suite->run();
