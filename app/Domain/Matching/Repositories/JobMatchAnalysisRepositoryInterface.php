<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Repositories;

use JobMarket\Domain\Matching\Entities\JobMatchAnalysis;

interface JobMatchAnalysisRepositoryInterface
{
    public function create(JobMatchAnalysis $analysis): void;

    public function findById(string $id): ?JobMatchAnalysis;

    public function findLatestByApplicationId(string $applicationId): ?JobMatchAnalysis;

    public function findByCacheKey(string $cacheKey): ?JobMatchAnalysis;

    /**
     * Atomically acquires an expired processing row or a retryable terminal row.
     * Returns false when another request owns the lease or the row is not eligible.
     */
    public function claimForProcessing(
        string $id,
        int $leaseSeconds,
        int $retryCooldownSeconds
    ): bool;

    /**
     * @return array<string, mixed>|null
     */
    public function findReusableCandidateExtraction(
        string $candidateHash,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel
    ): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findReusableJobExtraction(
        string $jobHash,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel
    ): ?array;

    public function updateStatus(string $id, string $status, ?string $failureCode = null): void;

    /**
     * @param array{
     *     overall_score: ?float,
     *     coverage_percent: ?float,
     *     classification: ?string,
     *     criteria_json: ?array,
     *     summary_json: ?array,
     *     duration_ms?: ?int,
     *     usage_metadata_json?: ?array,
     *     candidate_extraction_json?: ?array,
     *     job_extraction_json?: ?array
     * } $data
     */
    public function complete(string $id, array $data): void;

    /**
     * @param array{
     *     overall_score: ?float,
     *     coverage_percent: ?float,
     *     classification: ?string,
     *     criteria_json: ?array,
     *     summary_json: ?array,
     *     duration_ms?: ?int,
     *     usage_metadata_json?: ?array,
     *     candidate_extraction_json?: ?array,
     *     job_extraction_json?: ?array,
     *     failure_code?: ?string
     * } $data
     */
    public function markPartial(string $id, array $data): void;

    public function markFailed(string $id, string $failureCode): void;

    public function markRevoked(string $id): void;

    public function purgeByApplicationId(string $applicationId): void;
}
