<?php

declare(strict_types=1);

/**
 * CV-AI-P1-02 Automated Offline Regression Test Suite:
 * - MatchAnalysisService Orchestration
 * - Cache lookup & application isolation
 * - Fragment extraction reuse
 * - Safe failure isolation & error codes allowlist
 * - Consent enforcement & revocation
 * - Cooldown & processing lease
 * - IDOR & student ownership authorization
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use JobMarket\Domain\Application\Application;
use JobMarket\Domain\Application\ApplicationRepositoryInterface;
use JobMarket\Domain\Assistant\Exceptions\GeminiRateLimitException;
use JobMarket\Domain\Assistant\Exceptions\GeminiServiceException;
use JobMarket\Domain\Assistant\Exceptions\GeminiTimeoutException;
use JobMarket\Domain\Assistant\GeminiClientInterface;
use JobMarket\Domain\Assistant\GeminiResponse;
use JobMarket\Domain\Job\Job;
use JobMarket\Domain\Job\JobRepositoryInterface;
use JobMarket\Domain\Matching\Adapters\CandidateProfileAdapter;
use JobMarket\Domain\Matching\Adapters\JobRequirementsAdapter;
use JobMarket\Domain\Matching\Entities\JobMatchAnalysis;
use JobMarket\Domain\Matching\Repositories\JobMatchAnalysisRepositoryInterface;
use JobMarket\Domain\Matching\Services\CanonicalHashService;
use JobMarket\Domain\Matching\Services\DeterministicMatcher;
use JobMarket\Domain\Matching\Services\Extraction\ExtractionValidationException;
use JobMarket\Domain\Matching\Services\Extraction\ExtractionValidator;
use JobMarket\Domain\Matching\Services\Extraction\GeminiExtractionAdapter;
use JobMarket\Domain\Matching\Services\MatchAnalysisService;
use JobMarket\Domain\Matching\Services\Normalizers\LocationNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\ScheduleNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Domain\Profile\Profile;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Support\Pagination;

class InMemoryJobMatchAnalysisRepository implements JobMatchAnalysisRepositoryInterface
{
    /** @var array<string, JobMatchAnalysis> */
    public array $analyses = [];

    public function create(JobMatchAnalysis $analysis): void
    {
        $this->analyses[$analysis->getId()] = $analysis;
    }

    public function findById(string $id): ?JobMatchAnalysis
    {
        return $this->analyses[$id] ?? null;
    }

    public function findLatestByApplicationId(string $applicationId): ?JobMatchAnalysis
    {
        $found = [];
        foreach ($this->analyses as $a) {
            if ($a->getApplicationId() === $applicationId) {
                $found[] = $a;
            }
        }
        if (empty($found)) {
            return null;
        }
        usort($found, fn($a, $b) => strcmp($b->getCreatedAt() ?? '', $a->getCreatedAt() ?? ''));
        return $found[0];
    }

    public function findByCacheKey(string $cacheKey): ?JobMatchAnalysis
    {
        foreach ($this->analyses as $a) {
            if ($a->getCacheKey() === $cacheKey) {
                return $a;
            }
        }
        return null;
    }

    public function claimForProcessing(string $id, int $leaseSeconds, int $retryCooldownSeconds): bool
    {
        $analysis = $this->analyses[$id] ?? null;
        if ($analysis === null) {
            return false;
        }
        $row = $analysis->toArray();
        $status = $analysis->getStatus();
        $eligible = false;
        if ($status === 'processing') {
            $leaseTime = $row['started_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? '';
            $eligible = strtotime($leaseTime) <= (time() - $leaseSeconds);
        } elseif (in_array($status, ['partial', 'failed'], true)) {
            $terminalTime = $row['completed_at'] ?? $row['updated_at'] ?? $row['created_at'] ?? '';
            $eligible = strtotime($terminalTime) <= (time() - $retryCooldownSeconds);
        }
        if (!$eligible) {
            return false;
        }
        foreach (['overall_score', 'coverage_percent', 'classification', 'criteria_json', 'summary_json', 'failure_code', 'duration_ms', 'usage_metadata_json', 'completed_at'] as $field) {
            $row[$field] = null;
        }
        $row['status'] = 'processing';
        $row['started_at'] = date('Y-m-d H:i:s');
        $row['updated_at'] = $row['started_at'];
        $this->analyses[$id] = JobMatchAnalysis::fromArray($row);
        return true;
    }

    public function findReusableCandidateExtraction(
        string $candidateHash,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel
    ): ?array {
        foreach ($this->analyses as $a) {
            if (
                $a->getCandidateHash() === $candidateHash
                && $a->getPromptVersion() === $promptVersion
                && $a->getSchemaVersion() === $schemaVersion
                && in_array($a->getStatus(), ['completed', 'partial'], true)
                && $a->getCandidateExtractionJson() !== null
            ) {
                return $a->getCandidateExtractionJson();
            }
        }
        return null;
    }

    public function findReusableJobExtraction(
        string $jobHash,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel
    ): ?array {
        foreach ($this->analyses as $a) {
            if (
                $a->getJobHash() === $jobHash
                && $a->getPromptVersion() === $promptVersion
                && $a->getSchemaVersion() === $schemaVersion
                && in_array($a->getStatus(), ['completed', 'partial'], true)
                && $a->getJobExtractionJson() !== null
            ) {
                return $a->getJobExtractionJson();
            }
        }
        return null;
    }

    public function updateStatus(string $id, string $status, ?string $failureCode = null): void
    {
        if (isset($this->analyses[$id])) {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = $status;
            $curr['failure_code'] = $failureCode;
            $curr['updated_at'] = date('Y-m-d H:i:s');
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function complete(string $id, array $data): void
    {
        if (isset($this->analyses[$id]) && $this->analyses[$id]->getStatus() === 'processing') {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'completed';
            $curr['overall_score'] = $data['overall_score'] ?? null;
            $curr['coverage_percent'] = $data['coverage_percent'] ?? null;
            $curr['classification'] = $data['classification'] ?? null;
            $curr['criteria_json'] = $data['criteria_json'] ?? null;
            $curr['summary_json'] = $data['summary_json'] ?? null;
            $curr['candidate_extraction_json'] = $data['candidate_extraction_json'] ?? $curr['candidate_extraction_json'];
            $curr['job_extraction_json'] = $data['job_extraction_json'] ?? $curr['job_extraction_json'];
            $curr['usage_metadata_json'] = $data['usage_metadata_json'] ?? null;
            $curr['duration_ms'] = $data['duration_ms'] ?? null;
            $curr['completed_at'] = date('Y-m-d H:i:s');
            $curr['updated_at'] = date('Y-m-d H:i:s');
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function markPartial(string $id, array $data): void
    {
        if (isset($this->analyses[$id]) && $this->analyses[$id]->getStatus() === 'processing') {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'partial';
            $curr['overall_score'] = $data['overall_score'] ?? null;
            $curr['coverage_percent'] = $data['coverage_percent'] ?? null;
            $curr['classification'] = $data['classification'] ?? null;
            $curr['criteria_json'] = $data['criteria_json'] ?? null;
            $curr['summary_json'] = $data['summary_json'] ?? null;
            $curr['candidate_extraction_json'] = $data['candidate_extraction_json'] ?? $curr['candidate_extraction_json'];
            $curr['job_extraction_json'] = $data['job_extraction_json'] ?? $curr['job_extraction_json'];
            $curr['usage_metadata_json'] = $data['usage_metadata_json'] ?? null;
            $curr['duration_ms'] = $data['duration_ms'] ?? null;
            $curr['failure_code'] = $data['failure_code'] ?? null;
            $curr['completed_at'] = date('Y-m-d H:i:s');
            $curr['updated_at'] = date('Y-m-d H:i:s');
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function markFailed(string $id, string $failureCode): void
    {
        if (isset($this->analyses[$id]) && $this->analyses[$id]->getStatus() === 'processing') {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'failed';
            $curr['failure_code'] = $failureCode;
            $curr['completed_at'] = date('Y-m-d H:i:s');
            $curr['updated_at'] = date('Y-m-d H:i:s');
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function markRevoked(string $id): void
    {
        if (isset($this->analyses[$id])) {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'revoked';
            $curr['failure_code'] = 'CONSENT_REVOKED';
            $curr['candidate_snapshot_json'] = null;
            $curr['candidate_extraction_json'] = null;
            $curr['criteria_json'] = null;
            $curr['summary_json'] = null;
            $curr['overall_score'] = null;
            $curr['coverage_percent'] = null;
            $curr['classification'] = null;
            $curr['completed_at'] = date('Y-m-d H:i:s');
            $curr['updated_at'] = date('Y-m-d H:i:s');
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function purgeByApplicationId(string $applicationId): void
    {
        foreach ($this->analyses as $id => $a) {
            if ($a->getApplicationId() === $applicationId) {
                $this->markRevoked($id);
            }
        }
    }
}

class InMemoryApplicationRepository implements ApplicationRepositoryInterface
{
    public array $applications = [];

    public function create(Application $application): void
    {
        $this->applications[$application->getId()] = [
            'id' => $application->getId(),
            'job_id' => $application->getJobId(),
            'developer_id' => $application->getDeveloperId(),
            'status' => $application->getStatus(),
            'preferred_shift' => $application->getPreferredShift(),
            'ai_match_consent' => $application->getAiMatchConsent(),
            'ai_match_consented_at' => $application->getAiMatchConsentedAt(),
            'ai_match_consent_revoked_at' => $application->getAiMatchConsentRevokedAt(),
            'ai_match_notice_version' => $application->getAiMatchNoticeVersion(),
        ];
    }

    public function findById(string $id): ?array
    {
        return $this->applications[$id] ?? null;
    }

    public function updateConsent(string $id, bool $consent, ?string $revokedAt = null): void
    {
        if (isset($this->applications[$id])) {
            $this->applications[$id]['ai_match_consent'] = $consent;
            $this->applications[$id]['ai_match_consent_revoked_at'] = $revokedAt;
        }
    }

    public function updateStatus(string $id, string $status, ?string $studentMessage = null, ?string $actorId = null, string $actorRole = 'system', ?string $historyNote = null): void
    {
        if (isset($this->applications[$id])) {
            $this->applications[$id]['status'] = $status;
        }
    }

    public function findByJobAndStudent(string $jobId, string $studentUserId): ?array { return null; }
    public function getByStudent(string $studentUserId, array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countByStudent(string $studentUserId, array $filters = []): int { return 0; }
    public function getByJob(string $jobId, array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countByJob(string $jobId, array $filters = []): int { return 0; }
    public function getByCompany(string $companyId, array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countByCompany(string $companyId, array $filters = []): int { return 0; }
    public function withdraw(string $id, ?string $actorId = null, string $actorRole = 'student'): void {}
    public function getStatusHistory(string $applicationId): array { return []; }
}

class InMemoryProfileRepository implements ProfileRepositoryInterface
{
    public array $profiles = [];

    public function findByUserId(string $userId): ?array
    {
        return $this->profiles[$userId] ?? null;
    }

    public function findById(string $id): ?array { return null; }
    public function upsert(Profile $profile): void {}
    public function updateCvMetadata(string $userId, ?array $cvData): void {}
    public function searchPublic(array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countPublic(array $filters = []): int { return 0; }
}

class InMemoryJobRepository implements JobRepositoryInterface
{
    public array $jobs = [];

    public function findById(string $id): array
    {
        return $this->jobs[$id] ?? [];
    }

    public function getAll(): array { return []; }
    public function search(array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function count(array $filters = []): int { return 0; }
    public function findByCompany(string $companyId, array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countByCompany(string $companyId, array $filters = []): int { return 0; }
    public function create(Job $job): void {}
    public function update(Job $job): void {}
    public function delete(string $id): void {}
    public function updateStatus(string $id, string $status): void {}
    public function close(string $id): void {}
    public function getExpiringJobs(int $daysThreshold = 3): array { return []; }
}

class SpyGeminiClient implements GeminiClientInterface
{
    public int $calls = 0;
    /** @var callable|null */
    public $responder = null;

    public function isAvailable(): bool
    {
        return true;
    }

    public function generateContent(array $contents, ?string $systemInstruction = null, array $options = []): GeminiResponse
    {
        $this->calls++;
        if ($this->responder !== null) {
            return call_user_func($this->responder, $contents, $systemInstruction);
        }

        if (str_contains($systemInstruction ?? '', 'candidate profile')) {
            $json = json_encode([
                'schema_version' => 'semantic-extraction.v1',
                'document_type' => 'candidate_profile',
                'skills' => [
                    ['canonical_name' => 'php', 'evidence' => 'PHP', 'confidence' => 0.9],
                ],
                'experience' => [],
                'education' => ['state' => 'UNKNOWN', 'level' => null, 'major' => null, 'evidence' => null, 'confidence' => 0.0],
                'schedule' => ['state' => 'UNKNOWN', 'slots' => [], 'evidence' => null, 'confidence' => 0.0],
                'role_terms' => ['php'],
            ]);
        } else {
            $json = json_encode([
                'schema_version' => 'semantic-extraction.v1',
                'document_type' => 'job_requirements',
                'skills' => [
                    ['canonical_name' => 'php', 'importance' => 'required', 'evidence' => 'PHP', 'confidence' => 0.9],
                ],
                'experience_requirement' => ['state' => 'UNKNOWN', 'minimum_months' => null, 'domains' => [], 'evidence' => null, 'confidence' => 0.0],
                'education_requirement' => ['state' => 'UNKNOWN', 'levels' => [], 'majors' => [], 'evidence' => null, 'confidence' => 0.0],
                'schedule_requirement' => ['state' => 'UNKNOWN', 'shift_type' => null, 'minimum_shifts_per_week' => null, 'evidence' => null, 'confidence' => 0.0],
                'role_terms' => ['php'],
            ]);
        }
        return new GeminiResponse($json, false, 'STOP', null, ['totalTokenCount' => 100]);
    }
}

class MatchingP102TestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=================================================================\n";
        echo "   CV-AI-P1-02 REGRESSION TEST SUITE (MatchAnalysisService)       \n";
        echo "=================================================================\n\n";

        $this->testConsentEnforcement();
        $this->testStudentOwnershipAuthorization();
        $this->testSuccessfulAnalysisAndSafeDto();
        $this->testCacheLookupZeroGeminiCalls();
        $this->testApplicationScopedCacheKeyIsolation();
        $this->testFragmentExtractionReuse();
        $this->testValidatedSemanticEnrichmentFeedsDeterministicMatcher();
        $this->testFailureIsolationWithSafeCodes();
        $this->testSafeFailureCodesAllowlist();
        $this->testCooldownAndProcessingLease();
        $this->testExpiredLeaseAndTerminalRetryAreReclaimed();
        $this->testConsentRevocationAndDataPurge();

        echo "\n-----------------------------------------------------------------\n";
        echo "Summary: {$this->passed} PASSED, {$this->failed} FAILED.\n";
        echo "-----------------------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$message}\n";
        } else {
            $this->failed++;
            echo " [FAIL] {$message}\n";
        }
    }

    private function createService(
        InMemoryJobMatchAnalysisRepository $analysisRepo,
        InMemoryApplicationRepository $appRepo,
        InMemoryProfileRepository $profRepo,
        InMemoryJobRepository $jobRepo,
        ?SpyGeminiClient $spyClient = null
    ): MatchAnalysisService {
        $skillNormalizer = new SkillNormalizer();
        $scheduleNormalizer = new ScheduleNormalizer();
        $locationNormalizer = new LocationNormalizer();
        $redactor = new PiiRedactor();

        $candidateAdapter = new CandidateProfileAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, $redactor);
        $jobAdapter = new JobRequirementsAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, [], $redactor);
        $matcher = new DeterministicMatcher();
        $hashService = new CanonicalHashService(str_repeat('s', 32));

        $extractionAdapter = null;
        if ($spyClient !== null) {
            $extractionAdapter = new GeminiExtractionAdapter($spyClient, $redactor, new ExtractionValidator());
        }

        return new MatchAnalysisService(
            analysisRepository: $analysisRepo,
            applicationRepository: $appRepo,
            profileRepository: $profRepo,
            jobRepository: $jobRepo,
            candidateAdapter: $candidateAdapter,
            jobAdapter: $jobAdapter,
            matcher: $matcher,
            hashService: $hashService,
            extractionAdapter: $extractionAdapter,
            geminiModel: 'gemini-1.5-flash'
        );
    }

    private function testConsentEnforcement(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();
        $spyClient = new SpyGeminiClient();
        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, $spyClient);

        // Case 1: ai_match_consent = false
        $appRepo->applications['app-no-consent'] = [
            'id' => 'app-no-consent',
            'job_id' => 'job-1',
            'developer_id' => 'student-1',
            'status' => 'pending',
            'ai_match_consent' => false,
            'ai_match_consent_revoked_at' => null,
        ];
        $res1 = $service->analyze(['id' => 'student-1', 'role' => 'student'], 'app-no-consent');
        $this->assert($res1['status'] === 'declined_consent', 'Omitted consent returns status declined_consent');
        $this->assert($res1['failure_code'] === 'CONSENT_REQUIRED', 'Omitted consent failure_code is CONSENT_REQUIRED');
        $this->assert($spyClient->calls === 0, 'Omitted consent makes 0 Gemini calls');

        // Case 2: ai_match_consent_revoked_at is set
        $appRepo->applications['app-revoked'] = [
            'id' => 'app-revoked',
            'job_id' => 'job-1',
            'developer_id' => 'student-1',
            'status' => 'pending',
            'ai_match_consent' => true,
            'ai_match_consent_revoked_at' => '2026-09-09 12:00:00',
        ];
        $res2 = $service->analyze(['id' => 'student-1', 'role' => 'student'], 'app-revoked');
        $this->assert($res2['status'] === 'revoked', 'Revoked consent returns status revoked');
        $this->assert($res2['failure_code'] === 'CONSENT_REVOKED', 'Revoked consent failure_code is CONSENT_REVOKED');
        $this->assert($spyClient->calls === 0, 'Revoked consent makes 0 Gemini calls');
    }

    private function testStudentOwnershipAuthorization(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();
        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo);

        $appRepo->applications['app-student-1'] = [
            'id' => 'app-student-1',
            'job_id' => 'job-1',
            'developer_id' => 'student-1',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];

        // 1. Student-2 tries to access Student-1 application
        $caughtForbidden = false;
        try {
            $service->analyze(['id' => 'student-2', 'role' => 'student'], 'app-student-1');
        } catch (AuthorizationException $e) {
            $caughtForbidden = true;
        }
        $this->assert($caughtForbidden, 'Student-2 accessing Student-1 application throws AuthorizationException (403)');

        // 2. Company tries to access student match analysis
        $caughtCompany = false;
        try {
            $service->analyze(['id' => 'company-user', 'role' => 'company'], 'app-student-1');
        } catch (AuthorizationException $e) {
            $caughtCompany = true;
        }
        $this->assert($caughtCompany, 'Company user accessing student match analysis throws AuthorizationException (403)');

        // 3. Admin tries to access student match analysis
        $caughtAdmin = false;
        try {
            $service->analyze(['id' => 'admin-user', 'role' => 'admin'], 'app-student-1');
        } catch (AuthorizationException $e) {
            $caughtAdmin = true;
        }
        $this->assert($caughtAdmin, 'Admin accessing student match analysis throws AuthorizationException (403)');

        // 4. Non-existent application throws NotFoundException
        $caughtNotFound = false;
        try {
            $service->analyze(['id' => 'student-1', 'role' => 'student'], 'app-non-existent');
        } catch (NotFoundException $e) {
            $caughtNotFound = true;
        }
        $this->assert($caughtNotFound, 'Non-existent application throws NotFoundException (404)');
    }

    private function testSuccessfulAnalysisAndSafeDto(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();
        $spyClient = new SpyGeminiClient();

        $appRepo->applications['app-ok'] = [
            'id' => 'app-ok',
            'job_id' => 'job-10',
            'developer_id' => 'student-10',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-10'] = [
            'id' => 'sp-10',
            'user_id' => 'student-10',
            'full_name' => 'Tran Van A',
            'skills' => 'PHP, MySQL, Laravel',
            'major' => 'Cong nghe thong tin',
            'work_experience' => 'Thực tập sinh PHP backend tại công ty ABC',
            'available_schedule' => json_encode(['morning' => ['monday', 'wednesday']]),
        ];
        $jobRepo->jobs['job-10'] = [
            'id' => 'job-10',
            'title' => 'Backend PHP Developer Part-time',
            'required_skills' => 'PHP, MySQL',
            'shift_type' => 'morning',
            'description' => 'Tuyen lap trinh vien PHP lam viec buoi sang',
            'requirements' => 'Biet PHP co ban, uu tien sinh vien CNTT',
            'work_mode' => 'remote',
        ];

        // Mock Gemini responses with valid schema
        $spyClient->responder = function(array $contents, ?string $sys) {
            if (str_contains($sys ?? '', 'candidate profile')) {
                return new GeminiResponse(json_encode([
                    'schema_version' => 'semantic-extraction.v1',
                    'document_type' => 'candidate_profile',
                    'skills' => [
                        ['canonical_name' => 'php', 'evidence' => 'PHP', 'confidence' => 0.95],
                    ],
                    'experience' => [
                        ['role' => 'Thực tập sinh PHP', 'duration_months' => 6, 'domains' => ['backend'], 'evidence' => 'Thực tập sinh PHP backend', 'confidence' => 0.9],
                    ],
                    'education' => [
                        'state' => 'AVAILABLE',
                        'major' => 'Cong nghe thong tin',
                        'level' => 'Dai hoc',
                        'evidence' => 'Cong nghe thong tin',
                        'confidence' => 0.95
                    ],
                    'role_terms' => ['backend', 'php'],
                ]), false, 'STOP', null, ['totalTokenCount' => 120]);
            } else {
                return new GeminiResponse(json_encode([
                    'schema_version' => 'semantic-extraction.v1',
                    'document_type' => 'job_requirements',
                    'skills' => [
                        ['canonical_name' => 'php', 'importance' => 'required', 'evidence' => 'PHP', 'confidence' => 0.95],
                    ],
                    'experience_requirement' => [
                        'state' => 'AVAILABLE',
                        'minimum_months' => 3,
                        'domains' => ['backend'],
                        'evidence' => 'Biet PHP co ban',
                        'confidence' => 0.85
                    ],
                    'education_requirement' => [
                        'state' => 'AVAILABLE',
                        'levels' => ['dai hoc'],
                        'majors' => ['Cong nghe thong tin'],
                        'evidence' => 'uu tien sinh vien CNTT',
                        'confidence' => 0.85
                    ],
                    'schedule_requirement' => [
                        'state' => 'AVAILABLE',
                        'shift_type' => 'morning',
                        'minimum_shifts_per_week' => 2,
                        'evidence' => 'buoi sang',
                        'confidence' => 0.9
                    ],
                    'role_terms' => ['backend', 'developer'],
                ]), false, 'STOP', null, ['totalTokenCount' => 140]);
            }
        };

        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, $spyClient);
        $res = $service->analyze(['id' => 'student-10', 'role' => 'student'], 'app-ok');

        $this->assert($res['status'] === 'completed', 'Analysis finishes with status completed');
        $this->assert(isset($res['overall_score']) && $res['overall_score'] > 70.0, 'overall_score is calculated (> 70)');
        $this->assert(isset($res['coverage_percent']) && $res['coverage_percent'] >= 60.0, 'coverage_percent is calculated (>= 60)');
        $this->assert($res['classification'] === 'HIGH_MATCH' || $res['classification'] === 'GOOD_MATCH', 'classification is HIGH_MATCH or GOOD_MATCH');
        $this->assert(is_array($res['criteria']) && count($res['criteria']) === 5, 'Safe DTO contains exactly five published criteria');
        $this->assert(is_array($res['summary']['strengths']), 'Safe DTO contains summary.strengths');

        // Verify Safe DTO omits internals
        $this->assert(!isset($res['candidate_hash']), 'Safe DTO omits candidate_hash');
        $this->assert(!isset($res['job_hash']), 'Safe DTO omits job_hash');
        $this->assert(!isset($res['cache_key']), 'Safe DTO omits cache_key');
        $this->assert(!isset($res['candidate_snapshot_json']), 'Safe DTO omits candidate_snapshot_json');
        $this->assert(!isset($res['usage_metadata_json']), 'Safe DTO omits usage_metadata_json');

        // Verify applications.status is NEVER modified
        $this->assert($appRepo->applications['app-ok']['status'] === 'pending', 'applications.status remains strictly pending');
    }

    private function testCacheLookupZeroGeminiCalls(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();
        $spyClient = new SpyGeminiClient();

        $appRepo->applications['app-cache'] = [
            'id' => 'app-cache',
            'job_id' => 'job-10',
            'developer_id' => 'student-10',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-10'] = [
            'id' => 'sp-10',
            'user_id' => 'student-10',
            'skills' => 'PHP',
        ];
        $jobRepo->jobs['job-10'] = [
            'id' => 'job-10',
            'title' => 'PHP Dev',
            'required_skills' => 'PHP',
        ];

        // Seed a completed analysis in repo
        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, $spyClient);
        $res1 = $service->analyze(['id' => 'student-10', 'role' => 'student'], 'app-cache');
        $callsAfterFirst = $spyClient->calls;
        $this->assert($callsAfterFirst > 0, 'First analysis made Gemini calls');

        // Second analysis with identical application & data
        $res2 = $service->analyze(['id' => 'student-10', 'role' => 'student'], 'app-cache');
        $this->assert($spyClient->calls === $callsAfterFirst, 'Cache hit: Gemini was called 0 additional times');
        $this->assert($res2['status'] === 'completed', 'Cache hit returns completed analysis');
    }

    private function testApplicationScopedCacheKeyIsolation(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();
        $spyClient = new SpyGeminiClient();

        // Same student applies to job-10 twice under two different application IDs
        $appRepo->applications['app-1'] = [
            'id' => 'app-1',
            'job_id' => 'job-10',
            'developer_id' => 'student-10',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $appRepo->applications['app-2'] = [
            'id' => 'app-2',
            'job_id' => 'job-10',
            'developer_id' => 'student-10',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-10'] = [
            'id' => 'sp-10',
            'user_id' => 'student-10',
            'skills' => 'PHP',
        ];
        $jobRepo->jobs['job-10'] = [
            'id' => 'job-10',
            'title' => 'PHP Dev',
            'required_skills' => 'PHP',
        ];

        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, $spyClient);
        $res1 = $service->analyze(['id' => 'student-10', 'role' => 'student'], 'app-1');
        $res2 = $service->analyze(['id' => 'student-10', 'role' => 'student'], 'app-2');

        $this->assert($res1['analysis_id'] !== $res2['analysis_id'], 'Distinct applications produce distinct analysis records');

        $analyses = array_values($analysisRepo->analyses);
        $this->assert(count($analyses) === 2, 'Two separate analyses persisted');
        $this->assert($analyses[0]->getCacheKey() !== $analyses[1]->getCacheKey(), 'Distinct applications produce distinct cache keys (isolation)');
    }

    private function testFragmentExtractionReuse(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();
        $spyClient = new SpyGeminiClient();

        // Student applies to two DIFFERENT jobs (job-A, job-B)
        $appRepo->applications['app-jobA'] = [
            'id' => 'app-jobA',
            'job_id' => 'job-A',
            'developer_id' => 'student-reuse',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $appRepo->applications['app-jobB'] = [
            'id' => 'app-jobB',
            'job_id' => 'job-B',
            'developer_id' => 'student-reuse',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-reuse'] = [
            'id' => 'sp-reuse',
            'user_id' => 'student-reuse',
            'skills' => 'PHP, MySQL',
            'work_experience' => 'Backend dev 1 year',
        ];
        $jobRepo->jobs['job-A'] = [
            'id' => 'job-A',
            'title' => 'PHP Junior',
            'required_skills' => 'PHP',
        ];
        $jobRepo->jobs['job-B'] = [
            'id' => 'job-B',
            'title' => 'PHP Mid',
            'required_skills' => 'PHP, MySQL',
        ];

        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, $spyClient);

        // Run analysis for job-A: calls candidate extraction + job-A extraction
        $service->analyze(['id' => 'student-reuse', 'role' => 'student'], 'app-jobA');
        $callsAfterA = $spyClient->calls;
        $this->assert($callsAfterA === 2, 'First application calls candidate extraction (1) + job-A extraction (1) = 2');

        // Run analysis for job-B: candidate extraction is REUSED, only job-B is extracted
        $service->analyze(['id' => 'student-reuse', 'role' => 'student'], 'app-jobB');
        $this->assert($spyClient->calls === $callsAfterA + 1, 'Second application reuses candidate extraction: only 1 new call for job-B');
    }

    private function testValidatedSemanticEnrichmentFeedsDeterministicMatcher(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();
        $spy = new SpyGeminiClient();

        $appRepo->applications['app-semantic'] = [
            'id' => 'app-semantic',
            'job_id' => 'job-semantic',
            'developer_id' => 'student-semantic',
            'preferred_shift' => 'evening',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-semantic'] = [
            'id' => 'sp-semantic',
            'user_id' => 'student-semantic',
            'skills' => 'PHP',
            'work_experience' => 'Đã làm PHP developer trong 6 tháng.',
            'available_schedule' => json_encode(['monday' => ['evening']]),
        ];
        $jobRepo->jobs['job-semantic'] = [
            'id' => 'job-semantic',
            'title' => 'PHP Developer',
            'required_skills' => 'PHP',
            'requirements' => 'Yêu cầu 6 tháng kinh nghiệm, làm ca tối.',
        ];

        $spy->responder = static function(array $contents, ?string $systemInstruction): GeminiResponse {
            if (str_contains($systemInstruction ?? '', 'candidate profile')) {
                return new GeminiResponse(json_encode([
                    'schema_version' => 'semantic-extraction.v1',
                    'document_type' => 'candidate_profile',
                    'skills' => [['canonical_name' => 'php', 'evidence' => 'PHP', 'confidence' => 0.95]],
                    'experience' => [[
                        'role' => 'PHP developer',
                        'duration_months' => 6,
                        'domains' => ['PHP'],
                        'evidence' => 'PHP developer trong 6 tháng',
                        'confidence' => 0.95,
                    ]],
                    'education' => ['state' => 'UNKNOWN', 'level' => null, 'major' => null, 'evidence' => null, 'confidence' => 0.0],
                    'schedule' => ['state' => 'UNKNOWN', 'slots' => [], 'evidence' => null, 'confidence' => 0.0],
                    'role_terms' => ['php developer'],
                ]));
            }

            return new GeminiResponse(json_encode([
                'schema_version' => 'semantic-extraction.v1',
                'document_type' => 'job_requirements',
                'skills' => [['canonical_name' => 'php', 'importance' => 'required', 'evidence' => 'PHP', 'confidence' => 0.95]],
                'experience_requirement' => [
                    'state' => 'AVAILABLE',
                    'minimum_months' => 6,
                    'domains' => ['PHP'],
                    'evidence' => '6 tháng kinh nghiệm',
                    'confidence' => 0.95,
                ],
                'education_requirement' => ['state' => 'UNKNOWN', 'levels' => [], 'majors' => [], 'evidence' => null, 'confidence' => 0.0],
                'schedule_requirement' => [
                    'state' => 'AVAILABLE',
                    'shift_type' => 'evening',
                    'minimum_shifts_per_week' => null,
                    'evidence' => 'ca tối',
                    'confidence' => 0.95,
                ],
                'role_terms' => ['php developer'],
            ]));
        };

        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, $spy);
        $result = $service->analyze(['id' => 'student-semantic', 'role' => 'student'], 'app-semantic');
        $criteriaByName = [];
        foreach ($result['criteria'] ?? [] as $criterion) {
            $criteriaByName[$criterion['criterion_name'] ?? ''] = $criterion;
        }

        $this->assert(($criteriaByName['availability']['state'] ?? null) === 'AVAILABLE', 'Validated AI job shift fills UNKNOWN job schedule for PHP matcher');
        $this->assert(($criteriaByName['availability']['score'] ?? 0) >= 90, 'Semantic job shift is scored deterministically against structured availability');
        $this->assert(($criteriaByName['experience']['state'] ?? null) === 'AVAILABLE', 'Validated semantic experience feeds deterministic experience criterion');
        $this->assert(($criteriaByName['experience']['score'] ?? 0) >= 90, 'Extracted months/domain are scored by PHP, not by Gemini');
    }

    private function testFailureIsolationWithSafeCodes(): void
    {
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();

        $appRepo->applications['app-fail'] = [
            'id' => 'app-fail',
            'job_id' => 'job-fail',
            'developer_id' => 'student-fail',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-fail'] = [
            'id' => 'sp-fail',
            'user_id' => 'student-fail',
            'skills' => 'PHP',
            'work_experience' => 'Junior dev',
        ];
        $jobRepo->jobs['job-fail'] = [
            'id' => 'job-fail',
            'title' => 'PHP Developer',
            'required_skills' => 'PHP',
            'shift_type' => 'morning',
        ];

        $testCases = [
            [new GeminiTimeoutException("Request timed out", 408), 'AI_TIMEOUT', 'GeminiTimeoutException maps to AI_TIMEOUT'],
            [new GeminiRateLimitException("Rate limit reached", 429), 'AI_RATE_LIMITED', 'GeminiRateLimitException maps to AI_RATE_LIMITED'],
            [new GeminiServiceException("Service error", 503), 'AI_UNAVAILABLE', 'GeminiServiceException maps to AI_UNAVAILABLE'],
            [new \JobMarket\Domain\Matching\Services\Extraction\ExtractionValidationException(['json' => 'malformed']), 'AI_INVALID_RESPONSE', 'ExtractionValidationException maps to AI_INVALID_RESPONSE'],
        ];

        foreach ($testCases as [$exceptionToThrow, $expectedCode, $label]) {
            $analysisRepo = new InMemoryJobMatchAnalysisRepository();
            $spyClient = new SpyGeminiClient();
            $spyClient->responder = function() use ($exceptionToThrow) {
                throw $exceptionToThrow;
            };

            $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, $spyClient);
            $res = $service->analyze(['id' => 'student-fail', 'role' => 'student'], 'app-fail');

            $this->assert(in_array($res['status'], ['partial', 'failed'], true), "Failure handled gracefully (status {$res['status']})");
            $this->assert($res['failure_code'] === $expectedCode, $label);
            $this->assert($appRepo->applications['app-fail']['status'] === 'pending', 'applications.status preserved as pending despite AI failure');
        }

        // Test AI disabled case (null extractionAdapter)
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $serviceNoAi = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo, null);
        $resNoAi = $serviceNoAi->analyze(['id' => 'student-fail', 'role' => 'student'], 'app-fail');
        $this->assert($resNoAi['failure_code'] === 'AI_DISABLED', 'Missing adapter maps to AI_DISABLED');
        $this->assert($appRepo->applications['app-fail']['status'] === 'pending', 'applications.status untouched when AI disabled');
    }

    private function testSafeFailureCodesAllowlist(): void
    {
        $serviceCodes = MatchAnalysisService::SAFE_FAILURE_CODES;
        $expected = [
            'AI_DISABLED',
            'AI_TIMEOUT',
            'AI_RATE_LIMITED',
            'AI_UNAVAILABLE',
            'AI_INVALID_RESPONSE',
            'INSUFFICIENT_DATA',
            'CONSENT_REQUIRED',
            'CONSENT_REVOKED',
            'COOLDOWN_ACTIVE',
            'ANALYSIS_STALLED',
        ];

        foreach ($expected as $code) {
            $this->assert(in_array($code, $serviceCodes, true), "SAFE_FAILURE_CODES contains {$code}");
        }
    }

    private function testCooldownAndProcessingLease(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();

        $appRepo->applications['app-cd'] = [
            'id' => 'app-cd',
            'job_id' => 'job-cd',
            'developer_id' => 'student-cd',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-cd'] = [
            'id' => 'sp-cd',
            'user_id' => 'student-cd',
            'skills' => 'PHP',
        ];
        $jobRepo->jobs['job-cd'] = [
            'id' => 'job-cd',
            'title' => 'Dev',
            'required_skills' => 'PHP',
        ];

        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo);
        $firstRun = $service->analyze(['id' => 'student-cd', 'role' => 'student'], 'app-cd');
        $this->assert(in_array($firstRun['status'], ['completed', 'partial'], true), 'First analysis run succeeds');

        // Repeated run with identical inputs within 30 seconds triggers cooldown
        $secondRun = $service->analyze(['id' => 'student-cd', 'role' => 'student'], 'app-cd');
        $this->assert(!empty($secondRun['cooldown']), 'Repeated run within 30 seconds returns cooldown flag');
        $this->assert($secondRun['overall_score'] === $firstRun['overall_score'], 'Cooldown returns latest calculated overall_score');

        // If candidate profile changes (inputs change), cacheKey changes -> cooldown does NOT reuse old score
        $profRepo->profiles['student-cd']['skills'] = 'PHP, Python, Docker';
        $thirdRun = $service->analyze(['id' => 'student-cd', 'role' => 'student'], 'app-cd');
        $this->assert(empty($thirdRun['cooldown']), 'Changed profile does NOT return cooldown stale score');
    }

    private function testExpiredLeaseAndTerminalRetryAreReclaimed(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();

        $appRepo->applications['app-retry'] = [
            'id' => 'app-retry',
            'job_id' => 'job-retry',
            'developer_id' => 'student-retry',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-retry'] = [
            'id' => 'sp-retry',
            'user_id' => 'student-retry',
            'skills' => 'PHP',
        ];
        $jobRepo->jobs['job-retry'] = [
            'id' => 'job-retry',
            'title' => 'PHP Developer',
            'required_skills' => 'PHP',
        ];

        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo);
        $service->analyze(['id' => 'student-retry', 'role' => 'student'], 'app-retry');
        $id = array_key_first($analysisRepo->analyses);
        $row = $analysisRepo->analyses[$id]->toArray();
        $old = date('Y-m-d H:i:s', time() - MatchAnalysisService::COOLDOWN_SECONDS - 5);
        $row['status'] = 'partial';
        $row['failure_code'] = 'AI_DISABLED';
        $row['completed_at'] = $old;
        $row['updated_at'] = $old;
        $analysisRepo->analyses[$id] = JobMatchAnalysis::fromArray($row);

        $retried = $service->analyze(['id' => 'student-retry', 'role' => 'student'], 'app-retry');
        $this->assert(empty($retried['cooldown']), 'Expired partial result is atomically reclaimed for retry');
        $this->assert($retried['status'] === 'partial', 'Reclaimed retry reaches a terminal state instead of unique-key deadlock');

        $row = $analysisRepo->analyses[$id]->toArray();
        $staleStartedAt = date('Y-m-d H:i:s', time() - MatchAnalysisService::LEASE_DURATION_SECONDS - 5);
        $row['status'] = 'processing';
        $row['started_at'] = $staleStartedAt;
        $row['updated_at'] = $staleStartedAt;
        $row['completed_at'] = null;
        $analysisRepo->analyses[$id] = JobMatchAnalysis::fromArray($row);

        $stalledDto = $service->getAnalysis(['id' => 'student-retry', 'role' => 'student'], 'app-retry');
        $this->assert($stalledDto['status'] === 'failed', 'GET maps an expired processing lease to retryable failed state');
        $this->assert($stalledDto['failure_code'] === 'ANALYSIS_STALLED', 'Expired processing lease exposes only safe ANALYSIS_STALLED code');

        $reclaimed = $service->analyze(['id' => 'student-retry', 'role' => 'student'], 'app-retry');
        $this->assert($reclaimed['status'] === 'partial', 'Expired processing lease is reclaimed and completed without inserting duplicate cache key');
    }

    private function testConsentRevocationAndDataPurge(): void
    {
        $analysisRepo = new InMemoryJobMatchAnalysisRepository();
        $appRepo = new InMemoryApplicationRepository();
        $profRepo = new InMemoryProfileRepository();
        $jobRepo = new InMemoryJobRepository();

        $appRepo->applications['app-revoke'] = [
            'id' => 'app-revoke',
            'job_id' => 'job-rv',
            'developer_id' => 'student-rv',
            'status' => 'pending',
            'ai_match_consent' => true,
            'ai_match_consent_revoked_at' => null,
        ];

        // Seed existing completed analysis with candidate snapshot and extraction
        $existing = new JobMatchAnalysis(
            id: 'ana-to-revoke',
            applicationId: 'app-revoke',
            candidateSource: 'profile',
            candidateHash: str_repeat('a', 64),
            jobHash: str_repeat('b', 64),
            cacheKey: str_repeat('c', 64),
            matcherVersion: DeterministicMatcher::MATCHER_VERSION,
            promptVersion: GeminiExtractionAdapter::PROMPT_VERSION,
            schemaVersion: GeminiExtractionAdapter::SCHEMA_VERSION,
            status: 'completed',
            candidateSnapshotJson: ['skills' => ['PHP']],
            candidateExtractionJson: ['skills' => [['canonical_name' => 'php']]],
            criteriaJson: [['name' => 'skills', 'score' => 90]],
            summaryJson: ['strengths' => ['PHP']],
            overallScore: 90.0,
            coveragePercent: 90.0,
            classification: 'HIGH_MATCH'
        );
        $analysisRepo->create($existing);

        $service = $this->createService($analysisRepo, $appRepo, $profRepo, $jobRepo);

        // Revoke consent
        $revokeRes = $service->revokeConsent(['id' => 'student-rv', 'role' => 'student'], 'app-revoke');
        $this->assert($revokeRes['revoked'] === true, 'revokeConsent returns revoked: true');

        // Verify application updated
        $app = $appRepo->findById('app-revoke');
        $this->assert($app['ai_match_consent'] === false, 'Application ai_match_consent is now false');
        $this->assert(!empty($app['ai_match_consent_revoked_at']), 'Application ai_match_consent_revoked_at timestamp is set');
        $this->assert($app['status'] === 'pending', 'Application status remains strictly pending (not deleted or rejected)');

        // Verify analysis purged
        $purged = $analysisRepo->findById('ana-to-revoke');
        $this->assert($purged->getStatus() === 'revoked', 'Analysis status changed to revoked');
        $this->assert($purged->getCandidateSnapshotJson() === null, 'Candidate snapshot is purged (null)');
        $this->assert($purged->getCandidateExtractionJson() === null, 'Candidate extraction is purged (null)');
        $this->assert($purged->getCriteriaJson() === null, 'Criteria is purged (null)');
        $this->assert($purged->getOverallScore() === null, 'Overall score is reset to null');

        // Test idempotency: calling revokeConsent a second time doesn't error
        $revokeRes2 = $service->revokeConsent(['id' => 'student-rv', 'role' => 'student'], 'app-revoke');
        $this->assert($revokeRes2['revoked'] === true, 'Second revokeConsent call is idempotent');
    }
}

$suite = new MatchingP102TestSuite();
$suite->run();
