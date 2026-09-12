<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Entities;

use JsonSerializable;

class JobMatchAnalysis implements JsonSerializable
{
    private const SAFE_PUBLIC_FAILURE_CODES = [
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

    public function __construct(
        private string $id,
        private string $applicationId,
        private string $candidateSource,
        private string $candidateHash,
        private string $jobHash,
        private string $cacheKey,
        private string $matcherVersion,
        private string $promptVersion,
        private string $schemaVersion,
        private ?string $geminiModel = null,
        private string $status = 'processing',
        private int $consentSnapshot = 1,
        private ?array $candidateSnapshotJson = null,
        private ?array $jobSnapshotJson = null,
        private ?array $candidateExtractionJson = null,
        private ?array $jobExtractionJson = null,
        private ?array $criteriaJson = null,
        private ?array $summaryJson = null,
        private ?float $overallScore = null,
        private ?float $coveragePercent = null,
        private ?string $classification = null,
        private ?string $failureCode = null,
        private ?int $durationMs = null,
        private ?array $usageMetadataJson = null,
        private ?string $startedAt = null,
        private ?string $completedAt = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {
    }

    public static function createProcessing(
        string $applicationId,
        string $candidateHash,
        string $jobHash,
        string $cacheKey,
        string $matcherVersion,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel = null,
        int $consentSnapshot = 1,
        ?array $candidateSnapshotJson = null,
        ?array $jobSnapshotJson = null,
        ?string $id = null
    ): static {
        $now = date('Y-m-d H:i:s');
        return new static(
            id: $id ?? ('analysis-' . bin2hex(random_bytes(16))),
            applicationId: $applicationId,
            candidateSource: 'profile',
            candidateHash: $candidateHash,
            jobHash: $jobHash,
            cacheKey: $cacheKey,
            matcherVersion: $matcherVersion,
            promptVersion: $promptVersion,
            schemaVersion: $schemaVersion,
            geminiModel: $geminiModel,
            status: 'processing',
            consentSnapshot: $consentSnapshot,
            candidateSnapshotJson: $candidateSnapshotJson,
            jobSnapshotJson: $jobSnapshotJson,
            startedAt: $now,
            createdAt: $now,
            updatedAt: $now
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): static
    {
        $decodeJson = function(mixed $val): ?array {
            if (is_array($val)) {
                return $val;
            }
            if (is_string($val) && trim($val) !== '') {
                try {
                    $dec = json_decode($val, true, 512, JSON_THROW_ON_ERROR);
                    return is_array($dec) ? $dec : null;
                } catch (\JsonException $e) {
                    throw new \InvalidArgumentException("Malformed JSON in analysis record: " . $e->getMessage(), 0, $e);
                }
            }
            return null;
        };

        $candidateSource = (string)($row['candidate_source'] ?? 'profile');
        if ($candidateSource !== 'profile') {
            throw new \InvalidArgumentException("Invalid candidate_source: '{$candidateSource}'. Only 'profile' is supported in MVP.");
        }

        $status = (string)($row['status'] ?? 'processing');
        $allowedStatuses = ['processing', 'completed', 'partial', 'failed', 'revoked'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException("Invalid analysis status: {$status}");
        }

        $classification = $row['classification'] ?? null;
        if ($classification !== null) {
            $allowedClassifications = ['HIGH_MATCH', 'GOOD_MATCH', 'REVIEW_NEEDED', 'INSUFFICIENT_DATA'];
            if (!in_array($classification, $allowedClassifications, true)) {
                throw new \InvalidArgumentException("Invalid analysis classification: {$classification}");
            }
        }

        return new static(
            id: (string)$row['id'],
            applicationId: (string)$row['application_id'],
            candidateSource: $candidateSource,
            candidateHash: (string)$row['candidate_hash'],
            jobHash: (string)$row['job_hash'],
            cacheKey: (string)$row['cache_key'],
            matcherVersion: (string)$row['matcher_version'],
            promptVersion: (string)$row['prompt_version'],
            schemaVersion: (string)$row['schema_version'],
            geminiModel: $row['gemini_model'] ?? null,
            status: $status,
            consentSnapshot: (int)($row['consent_snapshot'] ?? 1),
            candidateSnapshotJson: $decodeJson($row['candidate_snapshot_json'] ?? null),
            jobSnapshotJson: $decodeJson($row['job_snapshot_json'] ?? null),
            candidateExtractionJson: $decodeJson($row['candidate_extraction_json'] ?? null),
            jobExtractionJson: $decodeJson($row['job_extraction_json'] ?? null),
            criteriaJson: $decodeJson($row['criteria_json'] ?? null),
            summaryJson: $decodeJson($row['summary_json'] ?? null),
            overallScore: isset($row['overall_score']) && $row['overall_score'] !== null ? (float)$row['overall_score'] : null,
            coveragePercent: isset($row['coverage_percent']) && $row['coverage_percent'] !== null ? (float)$row['coverage_percent'] : null,
            classification: $classification,
            failureCode: $row['failure_code'] ?? null,
            durationMs: isset($row['duration_ms']) && $row['duration_ms'] !== null ? (int)$row['duration_ms'] : null,
            usageMetadataJson: $decodeJson($row['usage_metadata_json'] ?? null),
            startedAt: $row['started_at'] ?? null,
            completedAt: $row['completed_at'] ?? null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->applicationId,
            'candidate_source' => $this->candidateSource,
            'candidate_hash' => $this->candidateHash,
            'job_hash' => $this->jobHash,
            'cache_key' => $this->cacheKey,
            'matcher_version' => $this->matcherVersion,
            'prompt_version' => $this->promptVersion,
            'schema_version' => $this->schemaVersion,
            'gemini_model' => $this->geminiModel,
            'status' => $this->status,
            'consent_snapshot' => $this->consentSnapshot,
            'candidate_snapshot_json' => $this->candidateSnapshotJson,
            'job_snapshot_json' => $this->jobSnapshotJson,
            'candidate_extraction_json' => $this->candidateExtractionJson,
            'job_extraction_json' => $this->jobExtractionJson,
            'criteria_json' => $this->criteriaJson,
            'summary_json' => $this->summaryJson,
            'overall_score' => $this->overallScore,
            'coverage_percent' => $this->coveragePercent,
            'classification' => $this->classification,
            'failure_code' => $this->failureCode,
            'duration_ms' => $this->durationMs,
            'usage_metadata_json' => $this->usageMetadataJson,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Presentation DTO for Student:
     * Strictly omits raw snapshots, hashes, and internals.
     *
     * @return array<string, mixed>
     */
    public function toSafeStudentDto(): array
    {
        $isInsufficient = ($this->coveragePercent !== null && $this->coveragePercent < 60.0)
            || $this->classification === 'INSUFFICIENT_DATA';
        $hideResult = in_array($this->status, ['processing', 'failed', 'revoked'], true);
        $safeFailureCode = $this->failureCode;
        if ($safeFailureCode !== null && !in_array($safeFailureCode, self::SAFE_PUBLIC_FAILURE_CODES, true)) {
            $safeFailureCode = 'AI_UNAVAILABLE';
        }

        return [
            'analysis_id' => $this->id,
            'status' => $this->status,
            'overall_score' => ($isInsufficient || $hideResult) ? null : $this->overallScore,
            'coverage_percent' => $hideResult ? null : $this->coveragePercent,
            'classification' => $hideResult ? null : $this->classification,
            'criteria' => $hideResult ? null : $this->criteriaJson,
            'summary' => $hideResult ? null : $this->summaryJson,
            'failure_code' => $safeFailureCode,
            'completed_at' => $this->completedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // Getters & Mutation Helpers
    public function getId(): string { return $this->id; }
    public function getApplicationId(): string { return $this->applicationId; }
    public function getCandidateSource(): string { return $this->candidateSource; }
    public function getCandidateHash(): string { return $this->candidateHash; }
    public function getJobHash(): string { return $this->jobHash; }
    public function getCacheKey(): string { return $this->cacheKey; }
    public function getMatcherVersion(): string { return $this->matcherVersion; }
    public function getPromptVersion(): string { return $this->promptVersion; }
    public function getSchemaVersion(): string { return $this->schemaVersion; }
    public function getGeminiModel(): ?string { return $this->geminiModel; }
    public function getStatus(): string { return $this->status; }
    public function getConsentSnapshot(): int { return $this->consentSnapshot; }
    public function getCandidateSnapshotJson(): ?array { return $this->candidateSnapshotJson; }
    public function getJobSnapshotJson(): ?array { return $this->jobSnapshotJson; }
    public function getCandidateExtractionJson(): ?array { return $this->candidateExtractionJson; }
    public function getJobExtractionJson(): ?array { return $this->jobExtractionJson; }
    public function getCriteriaJson(): ?array { return $this->criteriaJson; }
    public function getSummaryJson(): ?array { return $this->summaryJson; }
    public function getOverallScore(): ?float { return $this->overallScore; }
    public function getCoveragePercent(): ?float { return $this->coveragePercent; }
    public function getClassification(): ?string { return $this->classification; }
    public function getFailureCode(): ?string { return $this->failureCode; }
    public function getDurationMs(): ?int { return $this->durationMs; }
    public function getUsageMetadataJson(): ?array { return $this->usageMetadataJson; }
    public function getStartedAt(): ?string { return $this->startedAt; }
    public function getCompletedAt(): ?string { return $this->completedAt; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
