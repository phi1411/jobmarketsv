<?php

declare(strict_types=1);

/**
 * CV-AI-P1-03 Automated Offline Regression Test Suite:
 * - Student API Endpoints & Route Definitions
 * - IDOR Protection & Role Authentication
 * - Consent Revocation via PATCH
 * - Cache-Control & Safe Response Schema Invariants
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use JobMarket\Domain\Application\Application;
use JobMarket\Domain\Application\ApplicationRepositoryInterface;
use JobMarket\Domain\Assistant\RateLimiterInterface;
use JobMarket\Domain\Assistant\RateLimitResult;
use JobMarket\Domain\Job\Job;
use JobMarket\Domain\Job\JobRepositoryInterface;
use JobMarket\Domain\Matching\Adapters\CandidateProfileAdapter;
use JobMarket\Domain\Matching\Adapters\JobRequirementsAdapter;
use JobMarket\Domain\Matching\Entities\JobMatchAnalysis;
use JobMarket\Domain\Matching\Repositories\JobMatchAnalysisRepositoryInterface;
use JobMarket\Domain\Matching\Services\CanonicalHashService;
use JobMarket\Domain\Matching\Services\DeterministicMatcher;
use JobMarket\Domain\Matching\Services\MatchAnalysisService;
use JobMarket\Domain\Matching\Services\Normalizers\LocationNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\ScheduleNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Domain\Profile\Profile;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Http\Controllers\MatchAnalysisController;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class P103InMemoryAnalysisRepo implements JobMatchAnalysisRepositoryInterface
{
    public array $analyses = [];

    public function create(JobMatchAnalysis $analysis): void { $this->analyses[$analysis->getId()] = $analysis; }
    public function findById(string $id): ?JobMatchAnalysis { return $this->analyses[$id] ?? null; }
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
    public function claimForProcessing(string $id, int $leaseSeconds, int $retryCooldownSeconds): bool { return false; }
    public function findReusableCandidateExtraction(string $candHash, string $promptVer, string $schemaVer, ?string $geminiModel): ?array { return null; }
    public function findReusableJobExtraction(string $jobHash, string $promptVer, string $schemaVer, ?string $geminiModel): ?array { return null; }
    public function updateStatus(string $id, string $status, ?string $failureCode = null): void {}
    public function complete(string $id, array $data): void
    {
        if (isset($this->analyses[$id])) {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'completed';
            $curr['overall_score'] = $data['overall_score'] ?? null;
            $curr['coverage_percent'] = $data['coverage_percent'] ?? null;
            $curr['classification'] = $data['classification'] ?? null;
            $curr['criteria_json'] = $data['criteria_json'] ?? null;
            $curr['summary_json'] = $data['summary_json'] ?? null;
            $curr['completed_at'] = date('Y-m-d H:i:s');
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }
    public function markPartial(string $id, array $data): void
    {
        if (isset($this->analyses[$id])) {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'partial';
            $curr['overall_score'] = $data['overall_score'] ?? null;
            $curr['coverage_percent'] = $data['coverage_percent'] ?? null;
            $curr['classification'] = $data['classification'] ?? null;
            $curr['criteria_json'] = $data['criteria_json'] ?? null;
            $curr['summary_json'] = $data['summary_json'] ?? null;
            $curr['failure_code'] = $data['failure_code'] ?? null;
            $curr['completed_at'] = date('Y-m-d H:i:s');
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }
    public function markFailed(string $id, string $failureCode): void {}
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

class P103SpyRateLimiter implements RateLimiterInterface
{
    public int $consumeCalls = 0;
    public function consume(string $key, int $maxAttempts, int $decaySeconds = 60): RateLimitResult
    {
        $this->consumeCalls++;
        return new RateLimitResult(true, max(0, $maxAttempts - 1));
    }
    public function tooManyAttempts(string $key, int $maxAttempts): bool { return false; }
    public function hit(string $key, int $decaySeconds = 60): int { return 1; }
    public function availableIn(string $key): int { return 0; }
    public function clear(string $key): void {}
}

class P103InMemoryAppRepo implements ApplicationRepositoryInterface
{
    public array $applications = [];

    public function create(Application $application): void {}
    public function findById(string $id): ?array { return $this->applications[$id] ?? null; }
    public function updateConsent(string $id, bool $consent, ?string $revokedAt = null): void
    {
        if (isset($this->applications[$id])) {
            $this->applications[$id]['ai_match_consent'] = $consent;
            $this->applications[$id]['ai_match_consent_revoked_at'] = $revokedAt;
        }
    }
    public function updateStatus(string $id, string $status, ?string $employerNote = null): void {}
    public function findByJobAndStudent(string $jobId, string $studentUserId): ?array { return null; }
    public function getByStudent(string $studentUserId, array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countByStudent(string $studentUserId, array $filters = []): int { return 0; }
    public function getByJob(string $jobId, array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countByJob(string $jobId, array $filters = []): int { return 0; }
    public function getByCompany(string $companyId, array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countByCompany(string $companyId, array $filters = []): int { return 0; }
    public function withdraw(string $id): void {}
}

class P103InMemoryProfRepo implements ProfileRepositoryInterface
{
    public array $profiles = [];
    public function findByUserId(string $userId): ?array { return $this->profiles[$userId] ?? null; }
    public function findById(string $id): ?array { return null; }
    public function upsert(Profile $profile): void {}
    public function updateCvMetadata(string $userId, ?array $cvData): void {}
    public function searchPublic(array $filters = [], ?Pagination $pagination = null): array { return []; }
    public function countPublic(array $filters = []): int { return 0; }
}

class P103InMemoryJobRepo implements JobRepositoryInterface
{
    public array $jobs = [];
    public function findById(string $id): array { return $this->jobs[$id] ?? []; }
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
}

class MatchingP103TestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=================================================================\n";
        echo "   CV-AI-P1-03 REGRESSION TEST SUITE (MatchAnalysisController)   \n";
        echo "=================================================================\n\n";

        $this->testRouteRegistration();
        $this->testUnauthenticatedAccess();
        $this->testRoleAuthorizationEnforcement();
        $this->testIdorProtection();
        $this->testAuthorizationPrecedesRateLimiter();
        $this->testAnalyzeEndpointExecutionAndSafeDto();
        $this->testShowEndpointExecutionAndCacheControl();
        $this->testUpdateConsentEndpointValidationAndRevocation();

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

    private function createController(
        P103InMemoryAnalysisRepo $analysisRepo,
        P103InMemoryAppRepo $appRepo,
        P103InMemoryProfRepo $profRepo,
        P103InMemoryJobRepo $jobRepo,
        ?RateLimiterInterface $rateLimiter = null
    ): MatchAnalysisController {
        $skillNormalizer = new SkillNormalizer();
        $scheduleNormalizer = new ScheduleNormalizer();
        $locationNormalizer = new LocationNormalizer();
        $redactor = new PiiRedactor();

        $candidateAdapter = new CandidateProfileAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, $redactor);
        $jobAdapter = new JobRequirementsAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, [], $redactor);
        $matcher = new DeterministicMatcher();
        $hashService = new CanonicalHashService(str_repeat('k', 32));

        $service = new MatchAnalysisService(
            analysisRepository: $analysisRepo,
            applicationRepository: $appRepo,
            profileRepository: $profRepo,
            jobRepository: $jobRepo,
            candidateAdapter: $candidateAdapter,
            jobAdapter: $jobAdapter,
            matcher: $matcher,
            hashService: $hashService,
            extractionAdapter: null // Offline deterministic mode
        );

        return new MatchAnalysisController($service, $rateLimiter);
    }

    private function createFakeRequest(?array $user, array $body = []): Request
    {
        $req = new Request([], $body, [], [], []);
        if ($user !== null) {
            $req->setUser($user);
        }
        return $req;
    }

    private function testRouteRegistration(): void
    {
        $routes = require BASE_PATH . '/app/Routes/api.php';

        $foundAnalyze = false;
        $foundShow = false;
        $foundConsent = false;

        foreach ($routes as [$method, $path, $handler]) {
            if ($method === 'POST' && str_contains($path, '/match-analysis') && $handler[0] === MatchAnalysisController::class && $handler[1] === 'analyze') {
                $foundAnalyze = true;
            }
            if ($method === 'GET' && str_contains($path, '/match-analysis') && $handler[0] === MatchAnalysisController::class && $handler[1] === 'show') {
                $foundShow = true;
            }
            if ($method === 'PATCH' && str_contains($path, '/match-consent') && $handler[0] === MatchAnalysisController::class && $handler[1] === 'updateConsent') {
                $foundConsent = true;
            }
        }

        $this->assert($foundAnalyze, 'Route POST /applications/{id}/match-analysis registered');
        $this->assert($foundShow, 'Route GET /applications/{id}/match-analysis registered');
        $this->assert($foundConsent, 'Route PATCH /applications/{id}/match-consent registered');
    }

    private function testUnauthenticatedAccess(): void
    {
        $analysisRepo = new P103InMemoryAnalysisRepo();
        $appRepo = new P103InMemoryAppRepo();
        $profRepo = new P103InMemoryProfRepo();
        $jobRepo = new P103InMemoryJobRepo();
        $controller = $this->createController($analysisRepo, $appRepo, $profRepo, $jobRepo);

        $guestReq = $this->createFakeRequest(null);

        // 1. POST /applications/{id}/match-analysis
        $caught1 = false;
        try {
            $controller->analyze($guestReq, 'app-1');
        } catch (AuthenticationException $e) {
            $caught1 = true;
        }
        $this->assert($caught1, 'Unauthenticated POST /match-analysis throws AuthenticationException (401)');

        // 2. GET /applications/{id}/match-analysis
        $caught2 = false;
        try {
            $controller->show($guestReq, 'app-1');
        } catch (AuthenticationException $e) {
            $caught2 = true;
        }
        $this->assert($caught2, 'Unauthenticated GET /match-analysis throws AuthenticationException (401)');

        // 3. PATCH /applications/{id}/match-consent
        $caught3 = false;
        try {
            $controller->updateConsent($guestReq, 'app-1');
        } catch (AuthenticationException $e) {
            $caught3 = true;
        }
        $this->assert($caught3, 'Unauthenticated PATCH /match-consent throws AuthenticationException (401)');
    }

    private function testRoleAuthorizationEnforcement(): void
    {
        $analysisRepo = new P103InMemoryAnalysisRepo();
        $appRepo = new P103InMemoryAppRepo();
        $profRepo = new P103InMemoryProfRepo();
        $jobRepo = new P103InMemoryJobRepo();
        $controller = $this->createController($analysisRepo, $appRepo, $profRepo, $jobRepo);

        $appRepo->applications['app-1'] = [
            'id' => 'app-1',
            'job_id' => 'job-1',
            'developer_id' => 'student-owner',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];

        $rolesToReject = ['company', 'employer', 'admin', 'recruiter', 'guest'];

        foreach ($rolesToReject as $role) {
            $req = $this->createFakeRequest(['id' => 'other-user', 'role' => $role]);

            $caughtAnalyze = false;
            try {
                $controller->analyze($req, 'app-1');
            } catch (AuthorizationException $e) {
                $caughtAnalyze = true;
            }
            $this->assert($caughtAnalyze, "Role '{$role}' calling POST /match-analysis is blocked (403)");

            $caughtShow = false;
            try {
                $controller->show($req, 'app-1');
            } catch (AuthorizationException $e) {
                $caughtShow = true;
            }
            $this->assert($caughtShow, "Role '{$role}' calling GET /match-analysis is blocked (403)");

            $caughtConsent = false;
            try {
                $controller->updateConsent($req, 'app-1');
            } catch (AuthorizationException $e) {
                $caughtConsent = true;
            }
            $this->assert($caughtConsent, "Role '{$role}' calling PATCH /match-consent is blocked (403)");
        }
    }

    private function testIdorProtection(): void
    {
        $analysisRepo = new P103InMemoryAnalysisRepo();
        $appRepo = new P103InMemoryAppRepo();
        $profRepo = new P103InMemoryProfRepo();
        $jobRepo = new P103InMemoryJobRepo();
        $controller = $this->createController($analysisRepo, $appRepo, $profRepo, $jobRepo);

        // Application belongs to student-A
        $appRepo->applications['app-studentA'] = [
            'id' => 'app-studentA',
            'job_id' => 'job-1',
            'developer_id' => 'student-A',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];

        // student-B attempts to access student-A's application
        $reqStudentB = $this->createFakeRequest(['id' => 'student-B', 'role' => 'student'], ['consent' => false]);

        $caughtAnalyze = false;
        try {
            $controller->analyze($reqStudentB, 'app-studentA');
        } catch (AuthorizationException $e) {
            $caughtAnalyze = true;
        }
        $this->assert($caughtAnalyze, 'IDOR POST: Student B cannot analyze Student A application (403)');

        $caughtShow = false;
        try {
            $controller->show($reqStudentB, 'app-studentA');
        } catch (AuthorizationException $e) {
            $caughtShow = true;
        }
        $this->assert($caughtShow, 'IDOR GET: Student B cannot view Student A match analysis (403)');

        $caughtConsent = false;
        try {
            $controller->updateConsent($reqStudentB, 'app-studentA');
        } catch (AuthorizationException $e) {
            $caughtConsent = true;
        }
        $this->assert($caughtConsent, 'IDOR PATCH: Student B cannot revoke Student A consent (403)');
    }

    private function testAuthorizationPrecedesRateLimiter(): void
    {
        $analysisRepo = new P103InMemoryAnalysisRepo();
        $appRepo = new P103InMemoryAppRepo();
        $profRepo = new P103InMemoryProfRepo();
        $jobRepo = new P103InMemoryJobRepo();
        $rateLimiter = new P103SpyRateLimiter();
        $controller = $this->createController($analysisRepo, $appRepo, $profRepo, $jobRepo, $rateLimiter);

        $appRepo->applications['app-private'] = [
            'id' => 'app-private',
            'job_id' => 'job-private',
            'developer_id' => 'student-owner',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];

        try {
            $controller->analyze(
                $this->createFakeRequest(['id' => 'student-attacker', 'role' => 'student']),
                'app-private'
            );
        } catch (AuthorizationException) {
            // Expected.
        }
        $this->assert($rateLimiter->consumeCalls === 0, 'IDOR request is rejected before consuming rate-limit state');

        $appRepo->applications['app-declined'] = [
            'id' => 'app-declined',
            'job_id' => 'job-private',
            'developer_id' => 'student-owner',
            'status' => 'pending',
            'ai_match_consent' => false,
        ];
        $controller->analyze(
            $this->createFakeRequest(['id' => 'student-owner', 'role' => 'student']),
            'app-declined'
        );
        $this->assert($rateLimiter->consumeCalls === 0, 'Declined consent returns safely without consuming AI-analysis rate limit');
    }

    private function testAnalyzeEndpointExecutionAndSafeDto(): void
    {
        $analysisRepo = new P103InMemoryAnalysisRepo();
        $appRepo = new P103InMemoryAppRepo();
        $profRepo = new P103InMemoryProfRepo();
        $jobRepo = new P103InMemoryJobRepo();

        $appRepo->applications['app-exec'] = [
            'id' => 'app-exec',
            'job_id' => 'job-exec',
            'developer_id' => 'student-exec',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $profRepo->profiles['student-exec'] = [
            'id' => 'sp-exec',
            'user_id' => 'student-exec',
            'skills' => 'PHP, MySQL',
            'major' => 'CNTT',
        ];
        $jobRepo->jobs['job-exec'] = [
            'id' => 'job-exec',
            'title' => 'PHP Dev',
            'required_skills' => 'PHP',
            'work_mode' => 'remote',
        ];

        $controller = $this->createController($analysisRepo, $appRepo, $profRepo, $jobRepo);
        $req = $this->createFakeRequest(['id' => 'student-exec', 'role' => 'student']);

        $response = $controller->analyze($req, 'app-exec');

        $this->assert($response->getStatusCode() === Response::HTTP_OK, 'POST /match-analysis returns HTTP 200');
        $payload = $response->getPayload();
        $this->assert($payload['success'] === true, 'Response payload success is true');
        $data = $payload['data'];
        $this->assert(isset($data['analysis_id'], $data['status']) && array_key_exists('overall_score', $data), 'Response data contains safe student DTO');

        // Verify headers
        $headers = $response->getHeaders();
        $this->assert(isset($headers['Cache-Control']) && str_contains($headers['Cache-Control'], 'no-store'), 'POST response has Cache-Control: private, no-store');

        // Verify strict omission of internals
        $this->assert(!isset($data['candidate_hash']), 'Response strictly omits candidate_hash');
        $this->assert(!isset($data['job_hash']), 'Response strictly omits job_hash');
        $this->assert(!isset($data['cache_key']), 'Response strictly omits cache_key');
        $this->assert(!isset($data['candidate_snapshot_json']), 'Response strictly omits candidate_snapshot_json');
        $this->assert(!isset($data['job_snapshot_json']), 'Response strictly omits job_snapshot_json');
        $this->assert(!isset($data['usage_metadata_json']), 'Response strictly omits usage_metadata_json');
    }

    private function testShowEndpointExecutionAndCacheControl(): void
    {
        $analysisRepo = new P103InMemoryAnalysisRepo();
        $appRepo = new P103InMemoryAppRepo();
        $profRepo = new P103InMemoryProfRepo();
        $jobRepo = new P103InMemoryJobRepo();

        $appRepo->applications['app-show'] = [
            'id' => 'app-show',
            'job_id' => 'job-show',
            'developer_id' => 'student-show',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];

        // Seed existing analysis
        $analysis = new JobMatchAnalysis(
            id: 'ana-show-1',
            applicationId: 'app-show',
            candidateSource: 'profile',
            candidateHash: str_repeat('a', 64),
            jobHash: str_repeat('b', 64),
            cacheKey: str_repeat('c', 64),
            matcherVersion: DeterministicMatcher::MATCHER_VERSION,
            promptVersion: 'v1',
            schemaVersion: 'v1',
            status: 'completed',
            overallScore: 85.0,
            coveragePercent: 80.0,
            classification: 'HIGH_MATCH',
            criteriaJson: [['name' => 'skills', 'score' => 90]],
            summaryJson: ['strengths' => ['Kỹ năng phù hợp']]
        );
        $analysisRepo->create($analysis);

        $controller = $this->createController($analysisRepo, $appRepo, $profRepo, $jobRepo);
        $req = $this->createFakeRequest(['id' => 'student-show', 'role' => 'student']);

        $response = $controller->show($req, 'app-show');

        $this->assert($response->getStatusCode() === Response::HTTP_OK, 'GET /match-analysis returns HTTP 200');
        $payload = $response->getPayload();
        $this->assert($payload['success'] === true, 'Response success is true');
        $data = $payload['data'];
        $this->assert($data['analysis_id'] === 'ana-show-1', 'Returns matching analysis_id');
        $this->assert($data['overall_score'] === 85.0, 'Returns overall_score');

        $headers = $response->getHeaders();
        $this->assert(isset($headers['Cache-Control']) && str_contains($headers['Cache-Control'], 'no-store'), 'GET response has Cache-Control: private, no-store');
    }

    private function testUpdateConsentEndpointValidationAndRevocation(): void
    {
        $analysisRepo = new P103InMemoryAnalysisRepo();
        $appRepo = new P103InMemoryAppRepo();
        $profRepo = new P103InMemoryProfRepo();
        $jobRepo = new P103InMemoryJobRepo();

        $appRepo->applications['app-consent'] = [
            'id' => 'app-consent',
            'job_id' => 'job-c',
            'developer_id' => 'student-c',
            'status' => 'pending',
            'ai_match_consent' => true,
        ];
        $analysis = new JobMatchAnalysis(
            id: 'ana-to-revoke',
            applicationId: 'app-consent',
            candidateSource: 'profile',
            candidateHash: str_repeat('a', 64),
            jobHash: str_repeat('b', 64),
            cacheKey: str_repeat('c', 64),
            matcherVersion: DeterministicMatcher::MATCHER_VERSION,
            promptVersion: 'v1',
            schemaVersion: 'v1',
            status: 'completed',
            candidateSnapshotJson: ['skills' => ['PHP']],
            overallScore: 80.0
        );
        $analysisRepo->create($analysis);

        $controller = $this->createController($analysisRepo, $appRepo, $profRepo, $jobRepo);

        // 1. Missing consent field throws ValidationException (422)
        $reqMissing = $this->createFakeRequest(['id' => 'student-c', 'role' => 'student'], []);
        $caughtMissing = false;
        try {
            $controller->updateConsent($reqMissing, 'app-consent');
        } catch (ValidationException $e) {
            $caughtMissing = true;
        }
        $this->assert($caughtMissing, 'Missing consent field in body throws ValidationException (422)');

        // 2. consent: true is rejected (only revocation is supported)
        $reqTrue = $this->createFakeRequest(['id' => 'student-c', 'role' => 'student'], ['consent' => true]);
        $caughtTrue = false;
        try {
            $controller->updateConsent($reqTrue, 'app-consent');
        } catch (ValidationException $e) {
            $caughtTrue = true;
        }
        $this->assert($caughtTrue, 'Passing consent: true throws ValidationException (422)');

        // 3. Valid consent: false executes revocation
        $reqRevoke = $this->createFakeRequest(['id' => 'student-c', 'role' => 'student'], ['consent' => false]);
        $response = $controller->updateConsent($reqRevoke, 'app-consent');

        $this->assert($response->getStatusCode() === Response::HTTP_OK, 'PATCH /match-consent returns HTTP 200');
        $payload = $response->getPayload();
        $this->assert($payload['data']['revoked'] === true, 'Response reports revoked: true');

        // Verify application and analysis state
        $app = $appRepo->findById('app-consent');
        $this->assert($app['ai_match_consent'] === false, 'Application consent updated to false');
        $this->assert(!empty($app['ai_match_consent_revoked_at']), 'Application revoked_at timestamp set');

        $purged = $analysisRepo->findById('ana-to-revoke');
        $this->assert($purged->getStatus() === 'revoked', 'Analysis status changed to revoked');
        $this->assert($purged->getCandidateSnapshotJson() === null, 'Candidate snapshot purged');
        $this->assert($purged->getOverallScore() === null, 'Overall score reset');

        // Verify headers
        $headers = $response->getHeaders();
        $this->assert(isset($headers['Cache-Control']) && str_contains($headers['Cache-Control'], 'no-store'), 'PATCH response has Cache-Control: private, no-store');
    }
}

$suite = new MatchingP103TestSuite();
$suite->run();
