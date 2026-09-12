<?php

declare(strict_types=1);

namespace JobMarket\Infrastructure;

use JobMarket\Domain\Matching\Entities\JobMatchAnalysis;
use JobMarket\Domain\Matching\Repositories\JobMatchAnalysisRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class JobMatchAnalysisRepository implements JobMatchAnalysisRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            $config = Config::env();
            $this->db = new PDO(
                "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
                $config["user"],
                $config["password"],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
    }

    public function getDb(): PDO
    {
        return $this->db;
    }

    public function create(JobMatchAnalysis $analysis): void
    {
        if ($analysis->getCandidateSource() !== 'profile') {
            throw new \InvalidArgumentException("Candidate source must be 'profile'. Got: " . $analysis->getCandidateSource());
        }
        if ($analysis->getConsentSnapshot() !== 1) {
            throw new \InvalidArgumentException("Analysis can only be created when consent_snapshot is 1. Got: " . $analysis->getConsentSnapshot());
        }
        $this->validateStatus($analysis->getStatus());
        $this->validateClassification($analysis->getClassification());
        $this->validateScore($analysis->getOverallScore(), 'overall_score');
        $this->validateScore($analysis->getCoveragePercent(), 'coverage_percent');
        $this->validateHash($analysis->getCandidateHash(), 'candidate_hash');
        $this->validateHash($analysis->getJobHash(), 'job_hash');
        $this->validateHash($analysis->getCacheKey(), 'cache_key');

        $stmt = $this->db->prepare(
            "INSERT INTO `job_match_analyses` (
                `id`, `application_id`, `candidate_source`, `candidate_hash`, `job_hash`,
                `cache_key`, `matcher_version`, `prompt_version`, `schema_version`, `gemini_model`,
                `status`, `consent_snapshot`, `candidate_snapshot_json`, `job_snapshot_json`,
                `candidate_extraction_json`, `job_extraction_json`, `criteria_json`, `summary_json`,
                `overall_score`, `coverage_percent`, `classification`, `failure_code`, `duration_ms`,
                `usage_metadata_json`, `started_at`, `completed_at`
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?
            )"
        );

        $stmt->execute([
            $analysis->getId(),
            $analysis->getApplicationId(),
            $analysis->getCandidateSource(),
            $analysis->getCandidateHash(),
            $analysis->getJobHash(),
            $analysis->getCacheKey(),
            $analysis->getMatcherVersion(),
            $analysis->getPromptVersion(),
            $analysis->getSchemaVersion(),
            $analysis->getGeminiModel(),
            $analysis->getStatus(),
            $analysis->getConsentSnapshot(),
            $this->encodeJson($analysis->getCandidateSnapshotJson()),
            $this->encodeJson($analysis->getJobSnapshotJson()),
            $this->encodeJson($analysis->getCandidateExtractionJson()),
            $this->encodeJson($analysis->getJobExtractionJson()),
            $this->encodeJson($analysis->getCriteriaJson()),
            $this->encodeJson($analysis->getSummaryJson()),
            $analysis->getOverallScore(),
            $analysis->getCoveragePercent(),
            $analysis->getClassification(),
            $analysis->getFailureCode(),
            $analysis->getDurationMs(),
            $this->encodeJson($analysis->getUsageMetadataJson()),
            $analysis->getStartedAt() ?? date('Y-m-d H:i:s'),
            $analysis->getCompletedAt(),
        ]);
    }

    public function findById(string $id): ?JobMatchAnalysis
    {
        $stmt = $this->db->prepare("SELECT * FROM `job_match_analyses` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? JobMatchAnalysis::fromArray($row) : null;
    }

    public function findLatestByApplicationId(string $applicationId): ?JobMatchAnalysis
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `job_match_analyses` WHERE `application_id` = ? ORDER BY `created_at` DESC, `id` DESC LIMIT 1"
        );
        $stmt->execute([$applicationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? JobMatchAnalysis::fromArray($row) : null;
    }

    public function findByCacheKey(string $cacheKey): ?JobMatchAnalysis
    {
        $stmt = $this->db->prepare("SELECT * FROM `job_match_analyses` WHERE `cache_key` = ? LIMIT 1");
        $stmt->execute([$cacheKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? JobMatchAnalysis::fromArray($row) : null;
    }

    public function claimForProcessing(
        string $id,
        int $leaseSeconds,
        int $retryCooldownSeconds
    ): bool {
        $leaseSeconds = max(1, min(3600, $leaseSeconds));
        $retryCooldownSeconds = max(1, min(3600, $retryCooldownSeconds));
        $stmt = $this->db->prepare(
            "UPDATE `job_match_analyses`
             SET `status` = 'processing',
                 `overall_score` = NULL,
                 `coverage_percent` = NULL,
                 `classification` = NULL,
                 `criteria_json` = NULL,
                 `summary_json` = NULL,
                 `failure_code` = NULL,
                 `duration_ms` = NULL,
                 `usage_metadata_json` = NULL,
                 `started_at` = NOW(),
                 `completed_at` = NULL,
                 `updated_at` = NOW()
             WHERE `id` = ?
               AND (
                    (`status` = 'processing' AND COALESCE(`started_at`, `updated_at`, `created_at`) <= DATE_SUB(NOW(), INTERVAL {$leaseSeconds} SECOND))
                    OR
                    (`status` IN ('partial', 'failed') AND COALESCE(`completed_at`, `updated_at`, `created_at`) <= DATE_SUB(NOW(), INTERVAL {$retryCooldownSeconds} SECOND))
               )"
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }

    public function findReusableCandidateExtraction(
        string $candidateHash,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT `candidate_extraction_json` FROM `job_match_analyses`
             WHERE `candidate_hash` = ?
               AND `prompt_version` = ?
               AND `schema_version` = ?
               AND (`gemini_model` = ? OR (`gemini_model` IS NULL AND ? IS NULL))
               AND `status` IN ('completed', 'partial')
               AND `candidate_extraction_json` IS NOT NULL
             ORDER BY `created_at` DESC, `id` DESC LIMIT 1"
        );
        $stmt->execute([$candidateHash, $promptVersion, $schemaVersion, $geminiModel, $geminiModel]);
        $val = $stmt->fetchColumn();

        if ($val && is_string($val)) {
            return $this->decodeJson($val);
        }

        return null;
    }

    public function findReusableJobExtraction(
        string $jobHash,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT `job_extraction_json` FROM `job_match_analyses`
             WHERE `job_hash` = ?
               AND `prompt_version` = ?
               AND `schema_version` = ?
               AND (`gemini_model` = ? OR (`gemini_model` IS NULL AND ? IS NULL))
               AND `status` IN ('completed', 'partial')
               AND `job_extraction_json` IS NOT NULL
             ORDER BY `created_at` DESC, `id` DESC LIMIT 1"
        );
        $stmt->execute([$jobHash, $promptVersion, $schemaVersion, $geminiModel, $geminiModel]);
        $val = $stmt->fetchColumn();

        if ($val && is_string($val)) {
            return $this->decodeJson($val);
        }

        return null;
    }

    public function updateStatus(string $id, string $status, ?string $failureCode = null): void
    {
        $this->validateStatus($status);
        if ($status === 'revoked') {
            $this->markRevoked($id);
            return;
        }
        $stmt = $this->db->prepare(
            "UPDATE `job_match_analyses`
             SET `status` = ?, `failure_code` = ?, `updated_at` = NOW()
             WHERE `id` = ? AND `status` <> 'revoked'"
        );
        $stmt->execute([$status, $failureCode, $id]);
    }

    public function complete(string $id, array $data): void
    {
        $this->validateScore($data['overall_score'] ?? null, 'overall_score');
        $this->validateScore($data['coverage_percent'] ?? null, 'coverage_percent');
        $this->validateClassification($data['classification'] ?? null);

        $stmt = $this->db->prepare(
            "UPDATE `job_match_analyses`
             SET `status` = 'completed',
                 `overall_score` = ?,
                 `coverage_percent` = ?,
                 `classification` = ?,
                 `criteria_json` = ?,
                 `summary_json` = ?,
                 `candidate_extraction_json` = COALESCE(?, `candidate_extraction_json`),
                 `job_extraction_json` = COALESCE(?, `job_extraction_json`),
                 `usage_metadata_json` = ?,
                 `duration_ms` = ?,
                 `completed_at` = NOW(),
                 `updated_at` = NOW()
             WHERE `id` = ? AND `status` = 'processing'"
        );
        $stmt->execute([
            $data['overall_score'] ?? null,
            $data['coverage_percent'] ?? null,
            $data['classification'] ?? null,
            $this->encodeJson($data['criteria_json'] ?? null),
            $this->encodeJson($data['summary_json'] ?? null),
            $this->encodeJson($data['candidate_extraction_json'] ?? null),
            $this->encodeJson($data['job_extraction_json'] ?? null),
            $this->encodeJson($data['usage_metadata_json'] ?? null),
            $data['duration_ms'] ?? null,
            $id,
        ]);
    }

    public function markPartial(string $id, array $data): void
    {
        $this->validateScore($data['overall_score'] ?? null, 'overall_score');
        $this->validateScore($data['coverage_percent'] ?? null, 'coverage_percent');
        $this->validateClassification($data['classification'] ?? null);

        $stmt = $this->db->prepare(
            "UPDATE `job_match_analyses`
             SET `status` = 'partial',
                 `overall_score` = ?,
                 `coverage_percent` = ?,
                 `classification` = ?,
                 `criteria_json` = ?,
                 `summary_json` = ?,
                 `candidate_extraction_json` = COALESCE(?, `candidate_extraction_json`),
                 `job_extraction_json` = COALESCE(?, `job_extraction_json`),
                 `usage_metadata_json` = ?,
                 `duration_ms` = ?,
                 `failure_code` = ?,
                 `completed_at` = NOW(),
                 `updated_at` = NOW()
             WHERE `id` = ? AND `status` = 'processing'"
        );
        $stmt->execute([
            $data['overall_score'] ?? null,
            $data['coverage_percent'] ?? null,
            $data['classification'] ?? null,
            $this->encodeJson($data['criteria_json'] ?? null),
            $this->encodeJson($data['summary_json'] ?? null),
            $this->encodeJson($data['candidate_extraction_json'] ?? null),
            $this->encodeJson($data['job_extraction_json'] ?? null),
            $this->encodeJson($data['usage_metadata_json'] ?? null),
            $data['duration_ms'] ?? null,
            $data['failure_code'] ?? null,
            $id,
        ]);
    }

    public function markFailed(string $id, string $failureCode): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `job_match_analyses`
             SET `status` = 'failed',
                 `failure_code` = ?,
                 `completed_at` = NOW(),
                 `updated_at` = NOW()
             WHERE `id` = ? AND `status` = 'processing'"
        );
        $stmt->execute([$failureCode, $id]);
    }

    public function markRevoked(string $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `job_match_analyses`
             SET `status` = 'revoked',
                 `failure_code` = 'CONSENT_REVOKED',
                 `candidate_snapshot_json` = NULL,
                 `candidate_extraction_json` = NULL,
                 `criteria_json` = NULL,
                 `summary_json` = NULL,
                 `overall_score` = NULL,
                 `coverage_percent` = NULL,
                 `classification` = NULL,
                 `completed_at` = COALESCE(`completed_at`, NOW()),
                 `updated_at` = NOW()
             WHERE `id` = ?"
        );
        $stmt->execute([$id]);
    }

    public function purgeByApplicationId(string $applicationId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `job_match_analyses`
             SET `status` = 'revoked',
                 `failure_code` = 'CONSENT_REVOKED',
                 `candidate_snapshot_json` = NULL,
                 `candidate_extraction_json` = NULL,
                 `criteria_json` = NULL,
                 `summary_json` = NULL,
                 `overall_score` = NULL,
                 `coverage_percent` = NULL,
                 `classification` = NULL,
                 `completed_at` = COALESCE(`completed_at`, NOW()),
                 `updated_at` = NOW()
             WHERE `application_id` = ?"
        );
        $stmt->execute([$applicationId]);
    }

    private function encodeJson(mixed $data): ?string
    {
        if ($data === null) {
            return null;
        }
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function decodeJson(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException("Malformed JSON in repository: " . $e->getMessage(), 0, $e);
        }
    }

    public function validateStatus(string $status): void
    {
        $allowed = ['processing', 'completed', 'partial', 'failed', 'revoked'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid analysis status: {$status}");
        }
    }

    public function validateClassification(?string $classification): void
    {
        if ($classification === null) {
            return;
        }
        $allowed = ['HIGH_MATCH', 'GOOD_MATCH', 'REVIEW_NEEDED', 'INSUFFICIENT_DATA'];
        if (!in_array($classification, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid analysis classification: {$classification}");
        }
    }

    public function validateScore(?float $score, string $field): void
    {
        if ($score !== null && ($score < 0.0 || $score > 100.0)) {
            throw new \InvalidArgumentException("{$field} must be between 0.0 and 100.0. Got: {$score}");
        }
    }

    public function validateHash(string $hash, string $field): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/i', $hash)) {
            throw new \InvalidArgumentException("{$field} must be a 64-character hexadecimal string. Got: {$hash}");
        }
    }

    public function validateConsent(int $consent): void
    {
        if ($consent !== 0 && $consent !== 1) {
            throw new \InvalidArgumentException("consent_snapshot must be 0 or 1. Got: {$consent}");
        }
    }
}
