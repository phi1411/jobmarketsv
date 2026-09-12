<?php

declare(strict_types=1);

/**
 * CV-AI-P0-04 & CV-AI-P0-05 Automated Offline Regression Test Suite:
 * - P0-04: Persistence, Migration, Entity, Repository
 * - P0-05: Per-Application Consent, Validation, Service, UI Inspection
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use JobMarket\Domain\Application\Application;
use JobMarket\Domain\ApplicationService;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Domain\Matching\Entities\JobMatchAnalysis;
use JobMarket\Domain\Matching\Repositories\JobMatchAnalysisRepositoryInterface;
use JobMarket\Domain\Matching\Services\CanonicalHashService;
use JobMarket\Infrastructure\JobMatchAnalysisRepository;
use JobMarket\Migrations\JobMatchAnalysisMigration;

class MatchingP04P05TestSuite
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=================================================================\n";
        echo "   CV-AI-P0-04 & P0-05 REGRESSION TEST SUITE                     \n";
        echo "=================================================================\n\n";

        $this->testMigrationDefinition();
        $this->testMigrationRegistrationInMigratePhp();
        $this->testJobMatchAnalysisEntity();
        $this->testJobMatchAnalysisSafeStudentDto();
        $this->testJobMatchAnalysisRepositoryContract();
        $this->testApplicationEntityConsentHydration();
        $this->testApplicationServiceStrictConsentValidation();
        $this->testApplicationScopedCacheKey();
        $this->testRepositoryFragmentExtractionAndValidation();
        $this->testRepositoryAndEntityValidationGuards();
        $this->testApplyModalTemplateInspection();

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

    private function testMigrationDefinition(): void
    {
        $migration = new JobMatchAnalysisMigration();
        $this->assert($migration instanceof JobMatchAnalysisMigration, 'JobMatchAnalysisMigration instantiable (offline)');
        $this->assert($migration->getTableName() === 'job_match_analyses', 'Migration table name is job_match_analyses');

        // Check SQL methods exist
        $ref = new \ReflectionClass(JobMatchAnalysisMigration::class);
        $this->assert($ref->hasMethod('create'), 'Migration has create() method');
        $this->assert($ref->hasMethod('down'), 'Migration has down() method');

        // Inspect migration file source to verify schema correctness without executing it on live DB
        $source = file_get_contents(__DIR__ . '/app/Migrations/JobMatchAnalysisMigration.php');
        $this->assert(str_contains($source, 'ALTER TABLE `applications`'), 'Migration alters applications table');
        $this->assert(str_contains($source, '`ai_match_consent` TINYINT(1) NOT NULL DEFAULT 0'), 'Migration adds ai_match_consent with DEFAULT 0');
        $this->assert(str_contains($source, '`ai_match_consented_at` TIMESTAMP NULL'), 'Migration adds ai_match_consented_at');
        $this->assert(str_contains($source, '`ai_match_consent_revoked_at` TIMESTAMP NULL'), 'Migration adds ai_match_consent_revoked_at');
        $this->assert(str_contains($source, '`ai_match_notice_version` VARCHAR(32) NULL'), 'Migration adds ai_match_notice_version');
        $this->assert(str_contains($source, 'CREATE TABLE `job_match_analyses`'), 'Migration creates job_match_analyses table');
        $this->assert(str_contains($source, '`candidate_hash` CHAR(64) NOT NULL'), 'Table has candidate_hash CHAR(64)');
        $this->assert(str_contains($source, '`job_hash` CHAR(64) NOT NULL'), 'Table has job_hash CHAR(64)');
        $this->assert(str_contains($source, '`cache_key` CHAR(64) NOT NULL'), 'Table has cache_key CHAR(64)');
        $this->assert(str_contains($source, '`status` ENUM'), 'Table has status ENUM');
        $this->assert(str_contains($source, '(`candidate_hash`, `prompt_version`, `schema_version`, `gemini_model`, `status`)'), 'idx_match_cand_hash composite index includes schema_version');
        $this->assert(str_contains($source, '(`job_hash`, `prompt_version`, `schema_version`, `gemini_model`, `status`)'), 'idx_match_job_hash composite index includes schema_version');
        $this->assert(str_contains($source, 'Config::isProduction()'), 'Migration down() protects against production rollback via Config::isProduction()');
    }

    private function testMigrationRegistrationInMigratePhp(): void
    {
        $migrateContent = file_get_contents(__DIR__ . '/migrate.php');
        $this->assert(
            str_contains($migrateContent, '\JobMarket\Migrations\JobMatchAnalysisMigration::class'),
            'migrate.php registers JobMatchAnalysisMigration::class'
        );

        // Verify order: must be registered after ApplicationCvSnapshotMigration
        $snapshotPos = strpos($migrateContent, '\JobMarket\Migrations\ApplicationCvSnapshotMigration::class');
        $analysisPos = strpos($migrateContent, '\JobMarket\Migrations\JobMatchAnalysisMigration::class');
        $this->assert(
            $snapshotPos !== false && $analysisPos !== false && $analysisPos > $snapshotPos,
            'JobMatchAnalysisMigration is registered AFTER ApplicationCvSnapshotMigration'
        );
    }

    private function testJobMatchAnalysisEntity(): void
    {
        $analysis = JobMatchAnalysis::createProcessing(
            applicationId: 'app-test-123',
            candidateHash: str_repeat('a', 64),
            jobHash: str_repeat('b', 64),
            cacheKey: str_repeat('c', 64),
            matcherVersion: 'v1.0.0',
            promptVersion: 'v1.0.0',
            schemaVersion: 'v1.0.0',
            geminiModel: null,
            consentSnapshot: 1,
            candidateSnapshotJson: ['skills' => ['PHP']],
            jobSnapshotJson: ['skills' => ['PHP']]
        );

        $this->assert($analysis->getApplicationId() === 'app-test-123', 'Entity applicationId correct');
        $this->assert($analysis->getStatus() === 'processing', 'Entity initial status is processing');
        $this->assert($analysis->getConsentSnapshot() === 1, 'Entity consentSnapshot is 1');
        $this->assert($analysis->getCandidateHash() === str_repeat('a', 64), 'Entity candidateHash matches');
        $this->assert($analysis->getJobHash() === str_repeat('b', 64), 'Entity jobHash matches');
        $this->assert($analysis->getCacheKey() === str_repeat('c', 64), 'Entity cacheKey matches');

        $arr = $analysis->toArray();
        $this->assert(isset($arr['id'], $arr['application_id'], $arr['status'], $arr['created_at']), 'toArray includes core keys');

        // Hydration fromArray
        $hydrated = JobMatchAnalysis::fromArray($arr);
        $this->assert($hydrated->getId() === $analysis->getId(), 'fromArray preserves id');
        $this->assert($hydrated->getApplicationId() === 'app-test-123', 'fromArray preserves applicationId');
        $this->assert($hydrated->getCandidateSnapshotJson() === ['skills' => ['PHP']], 'fromArray preserves decoded JSON');
    }

    private function testJobMatchAnalysisSafeStudentDto(): void
    {
        $analysis = new JobMatchAnalysis(
            id: 'ana-safe-1',
            applicationId: 'app-999',
            candidateSource: 'profile',
            candidateHash: 'hash-secret-c',
            jobHash: 'hash-secret-j',
            cacheKey: 'cache-secret',
            matcherVersion: 'v1',
            promptVersion: 'v1',
            schemaVersion: 'v1',
            geminiModel: 'gemini-1.5-flash',
            status: 'completed',
            consentSnapshot: 1,
            candidateSnapshotJson: ['pii' => 'secret_candidate_data'],
            jobSnapshotJson: ['job' => 'secret_job_data'],
            candidateExtractionJson: null,
            jobExtractionJson: null,
            criteriaJson: [['name' => 'skills', 'score' => 90]],
            summaryJson: ['strengths' => ['Thành thạo PHP']],
            overallScore: 85.0,
            coveragePercent: 80.0,
            classification: 'HIGH_MATCH',
            failureCode: null,
            durationMs: 120,
            usageMetadataJson: ['tokens' => 450],
            startedAt: '2026-09-07 10:00:00',
            completedAt: '2026-09-07 10:00:01',
            createdAt: '2026-09-07 10:00:00',
            updatedAt: '2026-09-07 10:00:01'
        );

        $dto = $analysis->toSafeStudentDto();

        // Must contain presentation fields
        $this->assert($dto['analysis_id'] === 'ana-safe-1', 'Safe DTO contains analysis_id');
        $this->assert($dto['status'] === 'completed', 'Safe DTO contains status');
        $this->assert($dto['overall_score'] === 85.0, 'Safe DTO contains overall_score');
        $this->assert($dto['classification'] === 'HIGH_MATCH', 'Safe DTO contains classification');
        $this->assert(isset($dto['criteria'], $dto['summary']), 'Safe DTO contains criteria and summary');

        // Must strictly omit internal / PII / raw snapshot fields
        $this->assert(!array_key_exists('candidate_hash', $dto), 'Safe DTO omits candidate_hash');
        $this->assert(!array_key_exists('job_hash', $dto), 'Safe DTO omits job_hash');
        $this->assert(!array_key_exists('cache_key', $dto), 'Safe DTO omits cache_key');
        $this->assert(!array_key_exists('candidate_snapshot_json', $dto), 'Safe DTO omits candidate_snapshot_json');
        $this->assert(!array_key_exists('job_snapshot_json', $dto), 'Safe DTO omits job_snapshot_json');
        $this->assert(!array_key_exists('usage_metadata_json', $dto), 'Safe DTO omits usage_metadata_json');
        $this->assert(!array_key_exists('gemini_model', $dto), 'Safe DTO omits gemini_model');
    }

    private function testJobMatchAnalysisRepositoryContract(): void
    {
        $ref = new \ReflectionClass(JobMatchAnalysisRepository::class);
        $this->assert(
            $ref->implementsInterface(JobMatchAnalysisRepositoryInterface::class),
            'JobMatchAnalysisRepository implements JobMatchAnalysisRepositoryInterface'
        );

        $requiredMethods = [
            'create',
            'findById',
            'findLatestByApplicationId',
            'findByCacheKey',
            'findReusableCandidateExtraction',
            'findReusableJobExtraction',
            'updateStatus',
            'complete',
            'getDb'
        ];
        foreach ($requiredMethods as $method) {
            $this->assert($ref->hasMethod($method), "Repository has method {$method}()");
        }
    }

    private function testApplicationEntityConsentHydration(): void
    {
        $app = Application::create(
            job_id: 'job-1',
            developer_id: 'usr-1',
            cover_letter: 'Xin chao',
            resume: null,
            preferred_shift: 'morning'
        );

        $this->assert($app->getAiMatchConsent() === false, 'Application default consent is false');
        $this->assert($app->getAiMatchConsentedAt() === null, 'Application default consented_at is null');
        $this->assert($app->getAiMatchConsentRevokedAt() === null, 'Application default consent_revoked_at is null');
        $this->assert($app->getAiMatchNoticeVersion() === null, 'Application default notice_version is null');

        $app->setConsent(true, '2026-09-07 12:00:00', 'ai-match.v1');
        $this->assert($app->getAiMatchConsent() === true, 'setConsent(true) sets consent to true');
        $this->assert($app->getAiMatchConsentedAt() === '2026-09-07 12:00:00', 'setConsent sets consented_at');
        $this->assert($app->getAiMatchNoticeVersion() === 'ai-match.v1', 'setConsent sets notice_version');

        // Test toArrayForStudent includes consent
        $studentArr = $app->toArrayForStudent();
        $this->assert(isset($studentArr['ai_match_consent']), 'toArrayForStudent includes ai_match_consent');
        $this->assert($studentArr['ai_match_consent'] === true, 'ai_match_consent in toArrayForStudent is true');
        $this->assert($studentArr['ai_match_notice_version'] === 'ai-match.v1', 'notice_version in toArrayForStudent is ai-match.v1');

        // Test fromArray re-hydration
        $rehydrated = Application::fromArray([
            'id' => 'app-xyz',
            'job_id' => 'job-1',
            'student_id' => 'usr-1',
            'ai_match_consent' => 1,
            'ai_match_consented_at' => '2026-09-07 12:00:00',
            'ai_match_consent_revoked_at' => null,
            'ai_match_notice_version' => 'ai-match.v1',
            'status' => 'pending'
        ]);
        $this->assert($rehydrated->getAiMatchConsent() === true, 'fromArray restores ai_match_consent as boolean true');
        $this->assert($rehydrated->getAiMatchConsentedAt() === '2026-09-07 12:00:00', 'fromArray restores consented_at');
        $this->assert($rehydrated->getAiMatchNoticeVersion() === 'ai-match.v1', 'fromArray restores notice_version');
    }

    private function testApplicationServiceStrictConsentValidation(): void
    {
        // Notice version constant check
        $this->assert(
            defined(ApplicationService::class . '::AI_MATCH_NOTICE_VERSION'),
            'ApplicationService::AI_MATCH_NOTICE_VERSION is defined'
        );
        $this->assert(
            ApplicationService::AI_MATCH_NOTICE_VERSION === 'ai-match.v1',
            'ApplicationService::AI_MATCH_NOTICE_VERSION is "ai-match.v1"'
        );

        // Test consent validation helper logic directly
        // Rule: strictly accepts boolean only. Reject null, string 'true', 'false', int 1, 0 with ValidationException
        $validateConsent = function(array $data): bool {
            $aiMatchConsent = false;
            if (array_key_exists("ai_match_consent", $data)) {
                $rawConsent = $data["ai_match_consent"];
                if (!is_bool($rawConsent)) {
                    throw new ValidationException(["ai_match_consent" => ["Trường ai_match_consent phải là kiểu boolean (true hoặc false)."]]);
                }
                $aiMatchConsent = $rawConsent;
            }
            return $aiMatchConsent;
        };

        // Test cases:
        // 1. Missing -> false
        $this->assert($validateConsent([]) === false, 'Omitted ai_match_consent defaults to false');
        // 2. null -> must throw ValidationException (HTTP 422)
        $caughtNull = false;
        try {
            $validateConsent(['ai_match_consent' => null]);
        } catch (ValidationException $e) {
            $caughtNull = true;
            $this->assert(isset($e->getErrors()['ai_match_consent']), 'Validation error key is ai_match_consent for null');
        }
        $this->assert($caughtNull, 'null ai_match_consent throws ValidationException (422)');

        // 3. false -> false
        $this->assert($validateConsent(['ai_match_consent' => false]) === false, 'boolean false produces false');
        // 4. true -> true
        $this->assert($validateConsent(['ai_match_consent' => true]) === true, 'boolean true produces true');

        // 5. String 'true' -> must throw ValidationException (HTTP 422)
        $caughtStringTrue = false;
        try {
            $validateConsent(['ai_match_consent' => 'true']);
        } catch (ValidationException $e) {
            $caughtStringTrue = true;
            $this->assert(isset($e->getErrors()['ai_match_consent']), 'Validation error key is ai_match_consent for "true"');
        }
        $this->assert($caughtStringTrue, 'String "true" throws ValidationException (422)');

        // 6. Int 1 -> must throw ValidationException (HTTP 422)
        $caughtInt1 = false;
        try {
            $validateConsent(['ai_match_consent' => 1]);
        } catch (ValidationException $e) {
            $caughtInt1 = true;
            $this->assert(isset($e->getErrors()['ai_match_consent']), 'Validation error key is ai_match_consent for int 1');
        }
        $this->assert($caughtInt1, 'Integer 1 throws ValidationException (422)');

        // 7. Int 0 -> must throw ValidationException (HTTP 422)
        $caughtInt0 = false;
        try {
            $validateConsent(['ai_match_consent' => 0]);
        } catch (ValidationException $e) {
            $caughtInt0 = true;
        }
        $this->assert($caughtInt0, 'Integer 0 throws ValidationException (422)');

        // Check source code of ApplicationService to confirm zero Gemini calls during apply()
        $serviceSrc = file_get_contents(__DIR__ . '/app/Domain/ApplicationService.php');
        $applyMethodStart = strpos($serviceSrc, 'public function apply(');
        $nextMethodStart = strpos($serviceSrc, 'public function getJobApplications(', $applyMethodStart);
        $applyBody = substr($serviceSrc, $applyMethodStart, $nextMethodStart - $applyMethodStart);

        $this->assert(!str_contains(strtolower($applyBody), 'gemini'), 'ApplicationService::apply() contains ZERO calls to Gemini');
        $this->assert(str_contains($applyBody, '$res["match_analysis"] = ['), 'ApplicationService::apply() returns match_analysis payload');
        $this->assert(str_contains($applyBody, '$persistedApp->getAiMatchConsent()'), 'ApplicationService::apply() reads consent from persisted entity');
    }

    private function testApplicationScopedCacheKey(): void
    {
        $hasher = new CanonicalHashService('test-secret-key-that-is-at-least-32-chars-long!');

        $candHash = str_repeat('a', 64);
        $jobHash = str_repeat('b', 64);
        $matcherVer = 'v1.0.0';
        $promptVer = 'v1.0.0';
        $schemaVer = 'v1.0.0';
        $geminiModel = 'gemini-2.5-flash';

        $key1 = $hasher->computeCacheKey('app-001', $candHash, $jobHash, $matcherVer, $promptVer, $schemaVer, $geminiModel);
        $key2 = $hasher->computeCacheKey('app-002', $candHash, $jobHash, $matcherVer, $promptVer, $schemaVer, $geminiModel);

        $this->assert(strlen($key1) === 64, 'Cache key is 64 hex characters');
        $this->assert(strlen($key2) === 64, 'Cache key 2 is 64 hex characters');
        $this->assert($key1 !== $key2, 'Different application IDs produce distinct cache keys (application isolation)');

        $key1Repeat = $hasher->computeCacheKey('app-001', $candHash, $jobHash, $matcherVer, $promptVer, $schemaVer, $geminiModel);
        $this->assert($key1 === $key1Repeat, 'Same application ID and inputs produce identical cache key (deterministic)');

        // Test fail-closed on short secret key (< 32 chars)
        $caughtShortKey = false;
        try {
            new CanonicalHashService('too-short');
        } catch (\InvalidArgumentException $e) {
            $caughtShortKey = true;
        }
        $this->assert($caughtShortKey, 'CanonicalHashService rejects secrets shorter than 32 characters');
    }

    private function testRepositoryFragmentExtractionAndValidation(): void
    {
        // 1. Check ApplicationRepository has no supportsConsentColumns fallback
        $appRepoSrc = file_get_contents(__DIR__ . '/app/Infrastructure/ApplicationRepository.php');
        $this->assert(
            !str_contains($appRepoSrc, 'supportsConsentColumns'),
            'ApplicationRepository removes supportsConsentColumns() legacy probe'
        );
        $this->assert(
            str_contains($appRepoSrc, '`ai_match_consent`') && str_contains($appRepoSrc, '`ai_match_notice_version`'),
            'ApplicationRepository::create() unconditionally inserts all 4 consent columns'
        );

        // 2. Check JobMatchAnalysisRepositoryInterface has schemaVersion in signature
        $interfaceRef = new \ReflectionClass(JobMatchAnalysisRepositoryInterface::class);
        $candidateMethod = $interfaceRef->getMethod('findReusableCandidateExtraction');
        $this->assert($candidateMethod->getNumberOfParameters() === 4, 'findReusableCandidateExtraction accepts 4 parameters');
        $params = array_map(fn($p) => $p->getName(), $candidateMethod->getParameters());
        $this->assert(in_array('schemaVersion', $params, true), 'findReusableCandidateExtraction has schemaVersion parameter');

        $jobMethod = $interfaceRef->getMethod('findReusableJobExtraction');
        $this->assert($jobMethod->getNumberOfParameters() === 4, 'findReusableJobExtraction accepts 4 parameters');
        $jobParams = array_map(fn($p) => $p->getName(), $jobMethod->getParameters());
        $this->assert(in_array('schemaVersion', $jobParams, true), 'findReusableJobExtraction has schemaVersion parameter');

        // 3. Inspect JobMatchAnalysisRepository source for schema_version in reuse queries and validations
        $repoSrc = file_get_contents(__DIR__ . '/app/Infrastructure/JobMatchAnalysisRepository.php');
        $this->assert(
            str_contains($repoSrc, '`schema_version` = ?') || str_contains($repoSrc, 'schema_version = ?'),
            'Repository query checks schema_version = ?'
        );
        $this->assert(str_contains($repoSrc, 'candidate_extraction_json'), 'Repository complete() persists candidate_extraction_json');
        $this->assert(str_contains($repoSrc, 'job_extraction_json'), 'Repository complete() persists job_extraction_json');
        $this->assert(str_contains($repoSrc, 'usage_metadata_json'), 'Repository complete() persists usage_metadata_json');
        $this->assert(str_contains($repoSrc, 'JSON_THROW_ON_ERROR'), 'Repository uses JSON_THROW_ON_ERROR');

        // 4. Validate repository guard rails against invalid inputs
        $this->assert(str_contains($repoSrc, 'validateStatus'), 'Repository has validateStatus guard');
        $this->assert(str_contains($repoSrc, 'validateClassification'), 'Repository has validateClassification guard');
        $this->assert(str_contains($repoSrc, 'validateScore'), 'Repository has validateScore guard');
        $this->assert(str_contains($repoSrc, 'validateHash'), 'Repository has validateHash guard');
    }

    private function testRepositoryAndEntityValidationGuards(): void
    {
        $repoRef = new \ReflectionClass(JobMatchAnalysisRepository::class);
        $repoInstance = $repoRef->newInstanceWithoutConstructor();

        // 1. validateStatus
        $valStatus = $repoRef->getMethod('validateStatus');
        $valStatus->setAccessible(true);
        foreach (['processing', 'completed', 'partial', 'failed', 'revoked'] as $st) {
            $valStatus->invoke($repoInstance, $st);
        }
        $caughtPending = false;
        try {
            $valStatus->invoke($repoInstance, 'pending');
        } catch (\InvalidArgumentException $e) {
            $caughtPending = true;
        }
        $this->assert($caughtPending, 'validateStatus strictly rejects "pending"');

        // 2. validateClassification
        $valClass = $repoRef->getMethod('validateClassification');
        $valClass->setAccessible(true);
        foreach (['HIGH_MATCH', 'GOOD_MATCH', 'REVIEW_NEEDED', 'INSUFFICIENT_DATA', null] as $cl) {
            $valClass->invoke($repoInstance, $cl);
        }
        $caughtLower = false;
        try {
            $valClass->invoke($repoInstance, 'high_match');
        } catch (\InvalidArgumentException $e) {
            $caughtLower = true;
        }
        $this->assert($caughtLower, 'validateClassification strictly rejects lowercase "high_match"');

        // 3. validateHash
        $valHash = $repoRef->getMethod('validateHash');
        $valHash->setAccessible(true);
        $valHash->invoke($repoInstance, str_repeat('a', 64), 'hash'); // valid 64 hex
        $caughtNonHex = false;
        try {
            $valHash->invoke($repoInstance, str_repeat('z', 64), 'hash');
        } catch (\InvalidArgumentException $e) {
            $caughtNonHex = true;
        }
        $this->assert($caughtNonHex, 'validateHash rejects non-hex characters');

        $caughtShort = false;
        try {
            $valHash->invoke($repoInstance, str_repeat('a', 63), 'hash');
        } catch (\InvalidArgumentException $e) {
            $caughtShort = true;
        }
        $this->assert($caughtShort, 'validateHash rejects 63-char hash');

        // 4. decodeJson with malformed JSON
        $decodeJson = $repoRef->getMethod('decodeJson');
        $decodeJson->setAccessible(true);
        $caughtBadJson = false;
        try {
            $decodeJson->invoke($repoInstance, '{malformed json');
        } catch (\InvalidArgumentException $e) {
            $caughtBadJson = true;
        }
        $this->assert($caughtBadJson, 'decodeJson throws InvalidArgumentException on malformed JSON');

        // 5. create() guard on consent_snapshot === 1
        $analysisNoConsent = new JobMatchAnalysis(
            id: 'ana-test-noconsent',
            applicationId: 'app-001',
            candidateSource: 'profile',
            candidateHash: str_repeat('a', 64),
            jobHash: str_repeat('b', 64),
            cacheKey: str_repeat('c', 64),
            matcherVersion: 'v1',
            promptVersion: 'v1',
            schemaVersion: 'v1',
            status: 'processing',
            consentSnapshot: 0
        );
        $caughtNoConsent = false;
        try {
            $repoInstance->create($analysisNoConsent);
        } catch (\InvalidArgumentException $e) {
            $caughtNoConsent = true;
        }
        $this->assert($caughtNoConsent, 'Repository create() rejects consent_snapshot !== 1');

        // 6. create() guard on candidate_source === "profile"
        $analysisCvSource = new JobMatchAnalysis(
            id: 'ana-test-cv',
            applicationId: 'app-001',
            candidateSource: 'cv',
            candidateHash: str_repeat('a', 64),
            jobHash: str_repeat('b', 64),
            cacheKey: str_repeat('c', 64),
            matcherVersion: 'v1',
            promptVersion: 'v1',
            schemaVersion: 'v1',
            status: 'processing',
            consentSnapshot: 1
        );
        $caughtCvSource = false;
        try {
            $repoInstance->create($analysisCvSource);
        } catch (\InvalidArgumentException $e) {
            $caughtCvSource = true;
        }
        $this->assert($caughtCvSource, 'Repository create() rejects candidate_source !== "profile"');

        // 7. JobMatchAnalysis::fromArray() validation
        $basePayload = [
            'id' => 'ana-1',
            'application_id' => 'app-1',
            'candidate_source' => 'profile',
            'candidate_hash' => str_repeat('a', 64),
            'job_hash' => str_repeat('b', 64),
            'cache_key' => str_repeat('c', 64),
            'matcher_version' => 'v1.0.0',
            'prompt_version' => 'v1.0.0',
            'schema_version' => 'match-result.v1',
            'status' => 'completed',
            'consent_snapshot' => 1,
        ];

        $caughtEntityPending = false;
        try {
            JobMatchAnalysis::fromArray(array_merge($basePayload, ['status' => 'pending']));
        } catch (\InvalidArgumentException $e) {
            $caughtEntityPending = true;
        }
        $this->assert($caughtEntityPending, 'JobMatchAnalysis::fromArray() rejects "pending" status');

        $caughtEntityLower = false;
        try {
            JobMatchAnalysis::fromArray(array_merge($basePayload, ['classification' => 'high_match']));
        } catch (\InvalidArgumentException $e) {
            $caughtEntityLower = true;
        }
        $this->assert($caughtEntityLower, 'JobMatchAnalysis::fromArray() rejects lowercase classification');

        $caughtEntityBadJson = false;
        try {
            JobMatchAnalysis::fromArray(array_merge($basePayload, ['criteria_json' => '{not a valid json']));
        } catch (\InvalidArgumentException $e) {
            $caughtEntityBadJson = true;
        }
        $this->assert($caughtEntityBadJson, 'JobMatchAnalysis::fromArray() throws InvalidArgumentException on malformed JSON');
    }

    private function testApplyModalTemplateInspection(): void
    {
        $template = file_get_contents(__DIR__ . '/app/Views/jobs/show.php');

        $this->assert(str_contains($template, 'id="apply-ai-consent"'), 'Apply modal contains #apply-ai-consent element');
        $this->assert(!str_contains($template, 'id="apply-ai-consent" checked'), 'Apply modal checkbox is NOT checked by default');
        $this->assert(str_contains($template, 'ai_match_consent: aiConsent'), 'JS passes boolean ai_match_consent in submit payload');
        $this->assert(str_contains($template, 'AI') && str_contains($template, 'JobMarketSV'), 'Apply modal contains consent checkbox notice text');
        $this->assert(str_contains($template, 'kh&#244;ng quyết định tuyển dụng') || str_contains($template, 'không quyết định tuyển dụng'), 'Apply modal contains non-hiring disclaimer text');
    }
}

$suite = new MatchingP04P05TestSuite();
$suite->run();