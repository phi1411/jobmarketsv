<?php

declare(strict_types=1);

/**
 * CV-AI-P0-03 Automated Offline Regression Test Suite:
 * Deterministic Matcher v1 (Scoring, Coverage, Missing Data, and Classification)
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
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Services\DeterministicMatcher;
use JobMarket\Domain\Matching\ValueObjects\AvailabilityValue;
use JobMarket\Domain\Matching\ValueObjects\CandidateLocationValue;
use JobMarket\Domain\Matching\ValueObjects\EducationRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\EducationValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceItem;
use JobMarket\Domain\Matching\ValueObjects\ExperienceRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceValue;
use JobMarket\Domain\Matching\ValueObjects\JobLocationValue;
use JobMarket\Domain\Matching\ValueObjects\RoleValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleSlot;
use JobMarket\Domain\Matching\ValueObjects\SkillItem;

class DeterministicMatcherTestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=================================================================\n";
        echo "   CV-AI-P0-03 DETERMINISTIC MATCHER V1 TEST SUITE              \n";
        echo "=================================================================\n\n";

        $this->testFullDataMatching();
        $this->testMissingSkillsHandling();
        $this->testMissingExperienceHandling();
        $this->testMissingScheduleHandling();
        $this->testSkillMatchingRulesExactSynonymRelated();
        $this->testAvailabilityCompatibilityAndConflict();
        $this->testDeterministicExperienceScoringMatrix();
        $this->testLocationMatchingRules();
        $this->testClassificationScoreBoundaries();
        $this->testCoverageBelow60InsufficientData();
        $this->testDeterministicRepeatability();
        $this->testMissingJobRequirementsScoringAndRoleRelevance();

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
     * 1. Full data case -> High match
     */
    private function testFullDataMatching(): void
    {
        $matcher = new DeterministicMatcher();

        $candidate = new CandidateProfileContract(
            skills: [
                new SkillItem(canonicalName: 'Thu ngân & POS', matchKey: 'thu ngan & pos'),
                new SkillItem(canonicalName: 'Giao tiếp khách hàng', matchKey: 'giao tiep khach hang'),
            ],
            availability: new AvailabilityValue(
                state: DataState::AVAILABLE,
                slots: [new ScheduleSlot(DayOfWeek::MONDAY, ShiftType::EVENING)],
                preferredShiftForApplication: ShiftType::EVENING
            ),
            experience: new ExperienceValue(
                state: DataState::AVAILABLE,
                items: [new ExperienceItem(role: 'Thu ngân')]
            ),
            education: new EducationValue(
                state: DataState::AVAILABLE,
                major: 'Công nghệ thông tin'
            ),
            locations: new CandidateLocationValue(
                state: DataState::AVAILABLE,
                locationIds: ['loc-001'],
                names: ['Hà Nội - Cầu Giấy']
            )
        );

        $job = new JobRequirementsContract(
            role: new RoleValue(title: 'Nhân viên thu ngân part-time'),
            skills: [
                new SkillItem(canonicalName: 'Thu ngân & POS', matchKey: 'thu ngan & pos', importance: 'required'),
                new SkillItem(canonicalName: 'Giao tiếp khách hàng', matchKey: 'giao tiep khach hang', importance: 'required'),
            ],
            experienceRequirement: new ExperienceRequirementValue(state: DataState::NOT_APPLICABLE),
            educationRequirement: new EducationRequirementValue(state: DataState::NOT_APPLICABLE),
            schedule: new ScheduleRequirementValue(
                state: DataState::AVAILABLE,
                shiftType: ShiftType::EVENING
            ),
            location: new JobLocationValue(
                state: DataState::AVAILABLE,
                locationId: 'loc-001'
            )
        );

        $result = $matcher->match($candidate, $job);

        $this->assert($result->overallScore !== null && $result->overallScore >= 80.0, 'Full data: overall score >= 80');
        $this->assert($result->coveragePercent >= 60.0, 'Full data: coverage >= 60');
        $this->assert($result->classification === MatchClassification::HIGH_MATCH, 'Full data: classification is HIGH_MATCH');
        $this->assert(!empty($result->strengths), 'Full data: has generated strengths');
    }

    /**
     * 2. Missing skills handling
     */
    private function testMissingSkillsHandling(): void
    {
        $matcher = new DeterministicMatcher();

        $candidate = new CandidateProfileContract(
            skills: [], // No skills
            availability: new AvailabilityValue(
                state: DataState::AVAILABLE,
                preferredShiftForApplication: ShiftType::EVENING
            )
        );

        $job = new JobRequirementsContract(
            skills: [new SkillItem(canonicalName: 'PHP', matchKey: 'php')],
            schedule: new ScheduleRequirementValue(state: DataState::AVAILABLE, shiftType: ShiftType::EVENING)
        );

        $result = $matcher->match($candidate, $job);

        // Find skills criterion
        $skillsCriterion = null;
        foreach ($result->criteria as $c) {
            if ($c->criterionName === CriterionName::SKILLS) {
                $skillsCriterion = $c;
                break;
            }
        }

        $this->assert($skillsCriterion !== null, 'Skills criterion evaluated');
        $this->assert($skillsCriterion->state === DataState::UNKNOWN, 'Candidate missing skills -> state UNKNOWN');
        $this->assert($skillsCriterion->score === null, 'Candidate missing skills -> score is null (not default 0)');
        $this->assert(isset($result->missingData['skills']), 'missing_data records skills as missing');
    }

    /**
     * 3. Missing experience handling
     */
    private function testMissingExperienceHandling(): void
    {
        $matcher = new DeterministicMatcher();

        // Case A: Job requires experience, candidate has none -> UNKNOWN
        $candidateA = new CandidateProfileContract(experience: new ExperienceValue(state: DataState::UNKNOWN, items: []));
        $jobA = new JobRequirementsContract(experienceRequirement: new ExperienceRequirementValue(state: DataState::AVAILABLE, minimumMonths: 6));
        $resA = $matcher->match($candidateA, $jobA);

        $expA = $this->getCriterion($resA, CriterionName::EXPERIENCE);
        $this->assert($expA->state === DataState::UNKNOWN, 'Exp missing when required -> UNKNOWN');
        $this->assert($expA->score === null, 'Exp missing -> score is null');

        // Case B: Job states no experience required -> NOT_APPLICABLE
        $jobB = new JobRequirementsContract(experienceRequirement: new ExperienceRequirementValue(state: DataState::NOT_APPLICABLE));
        $resB = $matcher->match($candidateA, $jobB);

        $expB = $this->getCriterion($resB, CriterionName::EXPERIENCE);
        $this->assert($expB->state === DataState::NOT_APPLICABLE, 'Exp not required by job -> NOT_APPLICABLE');
        $this->assert($expB->score === null, 'NOT_APPLICABLE exp -> score is null');
    }

    /**
     * 4. Missing schedule handling
     */
    private function testMissingScheduleHandling(): void
    {
        $matcher = new DeterministicMatcher();

        $candidate = new CandidateProfileContract(
            availability: new AvailabilityValue(state: DataState::UNKNOWN, slots: [], preferredShiftForApplication: null)
        );
        $job = new JobRequirementsContract(
            schedule: new ScheduleRequirementValue(state: DataState::AVAILABLE, shiftType: ShiftType::EVENING)
        );

        $result = $matcher->match($candidate, $job);
        $avail = $this->getCriterion($result, CriterionName::AVAILABILITY);

        $this->assert($avail->state === DataState::UNKNOWN, 'Missing schedule -> state UNKNOWN');
        $this->assert($avail->score === null, 'Missing schedule -> score null');
        $this->assert(!empty($result->considerations), 'Missing schedule generates consideration notice');
    }

    /**
     * 5. Skill matching rules: Exact (100%), Synonym (80%), Related (50%), No match (0%)
     */
    private function testSkillMatchingRulesExactSynonymRelated(): void
    {
        $matcher = new DeterministicMatcher();

        // 1. Exact match
        $candExact = new CandidateProfileContract(skills: [new SkillItem(canonicalName: 'Thu ngân & POS', matchKey: 'thu ngan & pos')]);
        $jobSkill = new JobRequirementsContract(skills: [new SkillItem(canonicalName: 'Thu ngân & POS', matchKey: 'thu ngan & pos')]);
        $resExact = $matcher->match($candExact, $jobSkill);
        $this->assert($this->getCriterion($resExact, CriterionName::SKILLS)->score === 100.0, 'Exact skill match = 100%');

        // 2. Approved Synonym: "thu ngân" vs "thu ngân & pos" -> 80%
        $candSyn = new CandidateProfileContract(skills: [new SkillItem(canonicalName: 'Thu ngân', matchKey: 'thu ngan')]);
        $resSyn = $matcher->match($candSyn, $jobSkill);
        $this->assert($this->getCriterion($resSyn, CriterionName::SKILLS)->score === 80.0, 'Approved synonym skill match = 80%');

        // 3. Substantial Word overlap: "giao tiếp tiếng anh" vs "giao tiếp" -> 50%
        $candRelated = new CandidateProfileContract(skills: [new SkillItem(canonicalName: 'Giao tiếp tiếng anh', matchKey: 'giao tiep tieng anh')]);
        $jobComm = new JobRequirementsContract(skills: [new SkillItem(canonicalName: 'Kỹ năng giao tiếp', matchKey: 'ky nang giao tiep')]);
        $resRelated = $matcher->match($candRelated, $jobComm);
        $this->assert($this->getCriterion($resRelated, CriterionName::SKILLS)->score === 50.0, 'Related skill overlap = 50%');

        // 4. No match: "Lập trình C++" vs "Pha chế cà phê" -> 0%
        $candUnrelated = new CandidateProfileContract(skills: [new SkillItem(canonicalName: 'Lập trình C++', matchKey: 'lap trinh c++')]);
        $jobBarista = new JobRequirementsContract(skills: [new SkillItem(canonicalName: 'Pha chế cà phê', matchKey: 'pha che ca phe')]);
        $resUnrelated = $matcher->match($candUnrelated, $jobBarista);
        $this->assert($this->getCriterion($resUnrelated, CriterionName::SKILLS)->score === 0.0, 'Completely unmatched skills = 0%');
    }

    /**
     * 6. Availability compatibility and conflict
     */
    private function testAvailabilityCompatibilityAndConflict(): void
    {
        $matcher = new DeterministicMatcher();

        // Case A: Compatible (Preferred shift matches AND weekly schedule has compatible slot) -> 100%
        $candA = new CandidateProfileContract(
            availability: new AvailabilityValue(
                state: DataState::AVAILABLE,
                slots: [new ScheduleSlot(DayOfWeek::MONDAY, ShiftType::EVENING)],
                preferredShiftForApplication: ShiftType::EVENING
            )
        );
        $jobA = new JobRequirementsContract(
            schedule: new ScheduleRequirementValue(state: DataState::AVAILABLE, shiftType: ShiftType::EVENING)
        );
        $resA = $matcher->match($candA, $jobA);
        $this->assert($this->getCriterion($resA, CriterionName::AVAILABILITY)->score === 100.0, 'Compatible shift with matching weekly slot = 100%');

        // Case A2: Weekly schedule missing, only application preferred shift -> cautious score (70.0)
        $candA2 = new CandidateProfileContract(
            availability: new AvailabilityValue(
                state: DataState::AVAILABLE,
                slots: [],
                preferredShiftForApplication: ShiftType::EVENING
            )
        );
        $resA2 = $matcher->match($candA2, $jobA);
        $this->assert($this->getCriterion($resA2, CriterionName::AVAILABILITY)->score === 70.0, 'Missing weekly schedule with preferred shift = 70% cautious score');

        // Case A3: Conflict - preferred shift matches BUT weekly schedule declared has NO matching slot -> score <= 40.0, consideration added
        $candA3 = new CandidateProfileContract(
            availability: new AvailabilityValue(
                state: DataState::AVAILABLE,
                slots: [new ScheduleSlot(DayOfWeek::MONDAY, ShiftType::MORNING)],
                preferredShiftForApplication: ShiftType::EVENING
            )
        );
        $resA3 = $matcher->match($candA3, $jobA);
        $this->assert($this->getCriterion($resA3, CriterionName::AVAILABILITY)->score <= 40.0, 'Conflict between preferred shift and weekly schedule <= 40%');
        $this->assert(!empty($resA3->considerations), 'Conflict generates consideration notice');

        // Case B: Incompatible shift (Morning only vs Evening job) -> low score & consideration, NO application rejection
        $candB = new CandidateProfileContract(
            availability: new AvailabilityValue(
                state: DataState::AVAILABLE,
                slots: [new ScheduleSlot(DayOfWeek::MONDAY, ShiftType::MORNING)],
                preferredShiftForApplication: ShiftType::MORNING
            )
        );
        $resB = $matcher->match($candB, $jobA);
        $this->assert($this->getCriterion($resB, CriterionName::AVAILABILITY)->score <= 20.0, 'Incompatible shift = low score');
        $this->assert(!empty($resB->considerations), 'Incompatible shift adds consideration note without rejecting');
    }

    /**
     * Deterministic experience scoring matrix
     */
    private function testDeterministicExperienceScoringMatrix(): void
    {
        $matcher = new DeterministicMatcher();

        // Matrix 1: 24 months req / candidate 24 months -> 100.0
        $job24 = new JobRequirementsContract(
            experienceRequirement: new ExperienceRequirementValue(
                state: DataState::AVAILABLE,
                minimumMonths: 24
            )
        );
        $cand24 = new CandidateProfileContract(
            experience: new ExperienceValue(
                state: DataState::AVAILABLE,
                items: [new ExperienceItem(role: 'Lập trình viên', durationMonths: 24)]
            )
        );
        $res24 = $matcher->match($cand24, $job24);
        $crit24 = $this->getCriterion($res24, CriterionName::EXPERIENCE);
        $this->assert($crit24->score === 100.0, 'Exp matrix: 24 months req / 24 months cand = 100%');

        // Matrix 2: 24 months req / candidate 6 months -> 25.0
        $cand6 = new CandidateProfileContract(
            experience: new ExperienceValue(
                state: DataState::AVAILABLE,
                items: [new ExperienceItem(role: 'Lập trình viên', durationMonths: 6)]
            )
        );
        $res6 = $matcher->match($cand6, $job24);
        $crit6 = $this->getCriterion($res6, CriterionName::EXPERIENCE);
        $this->assert($crit6->score === 25.0, 'Exp matrix: 24 months req / 6 months cand = 25%');

        // Matrix 3: 24 months req + domain 'Pha chế', candidate 24 months in 'Lập trình viên' (unrelated domain)
        // Duration = 100%, Domain = 0% -> Combined = 100 * 0.5 + 0 * 0.5 = 50.0 (never 85)
        $jobDomain = new JobRequirementsContract(
            experienceRequirement: new ExperienceRequirementValue(
                state: DataState::AVAILABLE,
                minimumMonths: 24,
                domains: ['Pha chế cà phê']
            )
        );
        $resUnrelatedDomain = $matcher->match($cand24, $jobDomain);
        $critUnrelatedDomain = $this->getCriterion($resUnrelatedDomain, CriterionName::EXPERIENCE);
        $this->assert($critUnrelatedDomain->score <= 50.0, 'Exp matrix: 24/24 months but unrelated domain <= 50% (never 85)');

        // Matrix 4: Domain match (Pha chế) + 24 months req, but duration unknown -> cautious ~75.0%
        $candDomainNoDuration = new CandidateProfileContract(
            experience: new ExperienceValue(
                state: DataState::AVAILABLE,
                items: [new ExperienceItem(role: 'Nhân viên pha chế cà phê', durationMonths: null)]
            )
        );
        $resDomainNoDur = $matcher->match($candDomainNoDuration, $jobDomain);
        $critDomainNoDur = $this->getCriterion($resDomainNoDur, CriterionName::EXPERIENCE);
        $this->assert($critDomainNoDur->score === 75.0, 'Exp matrix: Domain matched + unknown duration = 75% cautious');

        // Matrix 5: Only domain required (e.g. Pha chế), candidate role completely unrelated (Lập trình) -> score 0.0, never 85
        $jobOnlyDomain = new JobRequirementsContract(
            experienceRequirement: new ExperienceRequirementValue(
                state: DataState::AVAILABLE,
                minimumMonths: null,
                domains: ['Pha chế cà phê']
            )
        );
        $resOnlyDomain = $matcher->match($cand24, $jobOnlyDomain);
        $critOnlyDomain = $this->getCriterion($resOnlyDomain, CriterionName::EXPERIENCE);
        $this->assert($critOnlyDomain->score === 0.0, 'Exp matrix: Unrelated domain without months req = 0% (never 85)');
    }

    /**
     * 7. Location matching rules: remote (100%), exact ID (100%), district (80%), city (60%), mismatch (25%)
     */
    private function testLocationMatchingRules(): void
    {
        $matcher = new DeterministicMatcher();

        // Remote
        $cand = new CandidateProfileContract(locations: new CandidateLocationValue(state: DataState::AVAILABLE, locationIds: ['loc-1'], names: ['Hà Nội - Cầu Giấy']));
        $jobRemote = new JobRequirementsContract(location: new JobLocationValue(state: DataState::AVAILABLE, workMode: 'remote'));
        $resRemote = $matcher->match($cand, $jobRemote);
        $this->assert($this->getCriterion($resRemote, CriterionName::LOCATION)->score === 100.0, 'Remote location = 100%');

        // Exact ID
        $jobExact = new JobRequirementsContract(location: new JobLocationValue(state: DataState::AVAILABLE, locationId: 'loc-1', workMode: 'onsite'));
        $resExact = $matcher->match($cand, $jobExact);
        $this->assert($this->getCriterion($resExact, CriterionName::LOCATION)->score === 100.0, 'Exact location ID = 100%');

        // Same District
        $jobDist = new JobRequirementsContract(location: new JobLocationValue(state: DataState::AVAILABLE, district: 'Cầu Giấy', workMode: 'onsite'));
        $resDist = $matcher->match($cand, $jobDist);
        $this->assert($this->getCriterion($resDist, CriterionName::LOCATION)->score === 80.0, 'Same district = 80%');

        // Same City
        $jobCity = new JobRequirementsContract(location: new JobLocationValue(state: DataState::AVAILABLE, city: 'Hà Nội', district: 'Hà Đông', workMode: 'onsite'));
        $resCity = $matcher->match($cand, $jobCity);
        $this->assert($this->getCriterion($resCity, CriterionName::LOCATION)->score === 60.0, 'Same city = 60%');
    }

    /**
     * 8. Classification score boundaries:
     * Score >= 80: HIGH_MATCH
     * Score 65 - 79.9: GOOD_MATCH
     * Score < 65: REVIEW_NEEDED
     */
    private function testClassificationScoreBoundaries(): void
    {
        $matcher = new DeterministicMatcher();

        // We can test boundaries by mocking exact criteria via score math
        // Bound 1: 80.0 -> HIGH_MATCH
        $cand80 = new CandidateProfileContract(
            skills: [new SkillItem(canonicalName: 'PHP', matchKey: 'php')],
            availability: new AvailabilityValue(state: DataState::AVAILABLE, preferredShiftForApplication: ShiftType::EVENING),
            locations: new CandidateLocationValue(state: DataState::AVAILABLE, locationIds: ['loc-1'])
        );
        $job80 = new JobRequirementsContract(
            role: new RoleValue(title: 'PHP Developer', semanticTerms: [['canonical_name' => 'PHP', 'evidence' => '', 'confidence' => 1.0]]),
            skills: [new SkillItem(canonicalName: 'PHP', matchKey: 'php')],
            schedule: new ScheduleRequirementValue(state: DataState::AVAILABLE, shiftType: ShiftType::EVENING),
            location: new JobLocationValue(state: DataState::AVAILABLE, locationId: 'loc-1')
        );
        $res80 = $matcher->match($cand80, $job80);
        $this->assert($res80->classification === MatchClassification::HIGH_MATCH, 'Score >= 80 classifies as HIGH_MATCH');

        // Bound 2: Low score -> REVIEW_NEEDED
        $candLow = new CandidateProfileContract(
            skills: [new SkillItem(canonicalName: 'Java', matchKey: 'java')],
            availability: new AvailabilityValue(state: DataState::AVAILABLE, preferredShiftForApplication: ShiftType::MORNING),
            locations: new CandidateLocationValue(state: DataState::AVAILABLE, locationIds: ['loc-999'], names: ['Cà Mau'])
        );
        $jobLow = new JobRequirementsContract(
            role: new RoleValue(title: 'Kế toán'),
            skills: [new SkillItem(canonicalName: 'Kế toán MISA', matchKey: 'ke toan misa')],
            schedule: new ScheduleRequirementValue(state: DataState::AVAILABLE, shiftType: ShiftType::EVENING),
            location: new JobLocationValue(state: DataState::AVAILABLE, locationId: 'loc-1', city: 'Hà Nội', district: 'Cầu Giấy')
        );
        $resLow = $matcher->match($candLow, $jobLow);
        $this->assert($resLow->classification === MatchClassification::REVIEW_NEEDED, 'Score < 65 classifies as REVIEW_NEEDED');
    }

    /**
     * 9. Coverage below 60 -> INSUFFICIENT_DATA regardless of high partial score
     */
    private function testCoverageBelow60InsufficientData(): void
    {
        $matcher = new DeterministicMatcher();

        // Only Location is AVAILABLE (Weight 10 -> coverage 10%)
        $candidate = new CandidateProfileContract(
            skills: [],
            availability: new AvailabilityValue(state: DataState::UNKNOWN, slots: [], preferredShiftForApplication: null),
            experience: new ExperienceValue(state: DataState::UNKNOWN, items: []),
            education: new EducationValue(state: DataState::UNKNOWN),
            locations: new CandidateLocationValue(state: DataState::AVAILABLE, locationIds: ['loc-1'])
        );

        $job = new JobRequirementsContract(
            skills: [], // NOT_APPLICABLE
            schedule: new ScheduleRequirementValue(state: DataState::UNKNOWN),
            location: new JobLocationValue(state: DataState::AVAILABLE, locationId: 'loc-1')
        );

        $result = $matcher->match($candidate, $job);

        $this->assert($result->coveragePercent < 60.0, "Coverage is {$result->coveragePercent}% (< 60%)");
        $this->assert($result->classification === MatchClassification::INSUFFICIENT_DATA, 'Coverage < 60 enforces INSUFFICIENT_DATA');
    }

    /**
     * 10. Deterministic repeatability
     */
    private function testDeterministicRepeatability(): void
    {
        $matcher = new DeterministicMatcher();

        $candidate = new CandidateProfileContract(
            skills: [new SkillItem(canonicalName: 'PHP', matchKey: 'php')],
            availability: new AvailabilityValue(state: DataState::AVAILABLE, preferredShiftForApplication: ShiftType::EVENING)
        );
        $job = new JobRequirementsContract(
            skills: [new SkillItem(canonicalName: 'PHP', matchKey: 'php')],
            schedule: new ScheduleRequirementValue(state: DataState::AVAILABLE, shiftType: ShiftType::EVENING)
        );

        $run1 = $matcher->match($candidate, $job);
        $run2 = $matcher->match($candidate, $job);

        $this->assert($run1->overallScore === $run2->overallScore, 'Repeat run 1 vs 2: overall_score identical');
        $this->assert($run1->coveragePercent === $run2->coveragePercent, 'Repeat run 1 vs 2: coverage_percent identical');
        $this->assert($run1->classification === $run2->classification, 'Repeat run 1 vs 2: classification identical');
    }

    /**
     * 11. Missing data & deterministic scoring fixes:
     * - If job experience requirement is UNKNOWN -> criterion UNKNOWN (score null, never default 85)
     * - If job schedule/location is UNKNOWN -> does not penalize candidate (score null, not 0)
     * - Role relevance does not default to 50 when signals are absent
     */
    private function testMissingJobRequirementsScoringAndRoleRelevance(): void
    {
        $matcher = new DeterministicMatcher();

        // 1. Job experience requirement is UNKNOWN, candidate HAS experience
        $candidateWithExp = new CandidateProfileContract(
            experience: new ExperienceValue(
                state: DataState::AVAILABLE,
                items: [new ExperienceItem(role: 'Thu ngân', durationMonths: 6)]
            )
        );
        $jobExpUnknown = new JobRequirementsContract(
            experienceRequirement: new ExperienceRequirementValue(state: DataState::UNKNOWN)
        );
        $resExp = $matcher->match($candidateWithExp, $jobExpUnknown);
        $critExp = $this->getCriterion($resExp, CriterionName::EXPERIENCE);
        $this->assert($critExp->state === DataState::UNKNOWN, 'Job exp UNKNOWN -> criterion state UNKNOWN');
        $this->assert($critExp->score === null, 'Job exp UNKNOWN -> score is null (never default 85)');

        // 2. Job schedule is UNKNOWN -> candidate not penalized
        $candidateWithSched = new CandidateProfileContract(
            availability: new AvailabilityValue(
                state: DataState::AVAILABLE,
                slots: [new ScheduleSlot(DayOfWeek::MONDAY, ShiftType::MORNING)]
            )
        );
        $jobSchedUnknown = new JobRequirementsContract(
            schedule: new ScheduleRequirementValue(state: DataState::UNKNOWN)
        );
        $resSched = $matcher->match($candidateWithSched, $jobSchedUnknown);
        $critSched = $this->getCriterion($resSched, CriterionName::AVAILABILITY);
        $this->assert($critSched->state === DataState::UNKNOWN, 'Job schedule UNKNOWN -> criterion state UNKNOWN');
        $this->assert($critSched->score === null, 'Job schedule UNKNOWN -> score is null (no penalty)');

        // 3. Job location is UNKNOWN -> candidate not penalized
        $candidateWithLoc = new CandidateProfileContract(
            locations: new CandidateLocationValue(
                state: DataState::AVAILABLE,
                locationIds: ['loc-001'],
                names: ['Hà Nội']
            )
        );
        $jobLocUnknown = new JobRequirementsContract(
            location: new JobLocationValue(state: DataState::UNKNOWN)
        );
        $resLoc = $matcher->match($candidateWithLoc, $jobLocUnknown);
        $critLoc = $this->getCriterion($resLoc, CriterionName::LOCATION);
        $this->assert($critLoc->state === DataState::UNKNOWN, 'Job location UNKNOWN -> criterion state UNKNOWN');
        $this->assert($critLoc->score === null, 'Job location UNKNOWN -> score is null (no penalty)');

        // 4. Role relevance: candidate with signals but completely unrelated -> AVAILABLE, score 0.0
        $candidateUnrelated = new CandidateProfileContract(
            skills: [new SkillItem(canonicalName: 'Lập trình C++', matchKey: 'lap trinh c++')],
            experience: new ExperienceValue(
                state: DataState::AVAILABLE,
                items: [new ExperienceItem(role: 'Lập trình viên')]
            )
        );
        $jobServer = new JobRequirementsContract(
            role: new RoleValue(title: 'Nhân viên phục vụ bàn')
        );
        $resRoleUnrelated = $matcher->match($candidateUnrelated, $jobServer);
        $critRoleUnrelated = $this->getCriterion($resRoleUnrelated, CriterionName::ROLE_RELEVANCE);
        $this->assert($critRoleUnrelated->state === DataState::AVAILABLE, 'Role relevance with unrelated signals -> state AVAILABLE');
        $this->assert($critRoleUnrelated->score === 0.0, 'Role relevance with unrelated signals -> score 0.0 (not default 50)');

        // 4b. Role relevance: candidate without any skills or experience (missing data) -> UNKNOWN, score null
        $candidateNoSignals = new CandidateProfileContract(
            skills: [],
            experience: new ExperienceValue(state: DataState::UNKNOWN, items: [])
        );
        $resRoleNoSignals = $matcher->match($candidateNoSignals, $jobServer);
        $critRoleNoSignals = $this->getCriterion($resRoleNoSignals, CriterionName::ROLE_RELEVANCE);
        $this->assert($critRoleNoSignals->state === DataState::UNKNOWN, 'Role relevance without any signals -> state UNKNOWN');
        $this->assert($critRoleNoSignals->score === null, 'Role relevance without any signals -> score null (not default 50)');

        // 5. Role relevance: matching signals present -> AVAILABLE, >= 80.0
        $candidateRelated = new CandidateProfileContract(
            skills: [new SkillItem(canonicalName: 'Phục vụ', matchKey: 'phuc vu')]
        );
        $resRoleRelated = $matcher->match($candidateRelated, $jobServer);
        $critRoleRelated = $this->getCriterion($resRoleRelated, CriterionName::ROLE_RELEVANCE);
        $this->assert($critRoleRelated->state === DataState::AVAILABLE, 'Role relevance with signals -> state AVAILABLE');
        $this->assert($critRoleRelated->score !== null && $critRoleRelated->score >= 80.0, 'Role relevance with signals -> score >= 80');
    }

    private function getCriterion(MatchResultContract $result, CriterionName $name): \JobMarket\Domain\Matching\ValueObjects\CriterionEvaluation
    {
        foreach ($result->criteria as $c) {
            if ($c->criterionName === $name) {
                return $c;
            }
        }
        throw new \RuntimeException("Criterion '{$name->value}' not found in result.");
    }
}

$suite = new DeterministicMatcherTestSuite();
$suite->run();
