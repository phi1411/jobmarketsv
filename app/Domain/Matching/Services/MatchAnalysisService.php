<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services;

use JobMarket\Domain\Application\ApplicationRepositoryInterface;
use JobMarket\Domain\Assistant\Exceptions\GeminiException;
use JobMarket\Domain\Assistant\Exceptions\GeminiRateLimitException;
use JobMarket\Domain\Assistant\Exceptions\GeminiServiceException;
use JobMarket\Domain\Assistant\Exceptions\GeminiTimeoutException;
use JobMarket\Domain\Job\JobRepositoryInterface;
use JobMarket\Domain\Matching\Adapters\CandidateProfileAdapter;
use JobMarket\Domain\Matching\Adapters\JobRequirementsAdapter;
use JobMarket\Domain\Matching\Contracts\CandidateProfileContract;
use JobMarket\Domain\Matching\Contracts\JobRequirementsContract;
use JobMarket\Domain\Matching\Contracts\MatchResultContract;
use JobMarket\Domain\Matching\Entities\JobMatchAnalysis;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Repositories\JobMatchAnalysisRepositoryInterface;
use JobMarket\Domain\Matching\Services\Extraction\ExtractionValidationException;
use JobMarket\Domain\Matching\Services\Extraction\GeminiExtractionAdapter;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\ValueObjects\EducationRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\EducationValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceItem;
use JobMarket\Domain\Matching\ValueObjects\ExperienceRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ExperienceValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\SkillItem;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Facades\Config;
use JobMarket\Support\Logger;

class MatchAnalysisService
{
    public const LEASE_DURATION_SECONDS = 60;
    public const COOLDOWN_SECONDS = 30;

    public const SAFE_FAILURE_CODES = [
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
        private readonly JobMatchAnalysisRepositoryInterface $analysisRepository,
        private readonly ApplicationRepositoryInterface $applicationRepository,
        private readonly ProfileRepositoryInterface $profileRepository,
        private readonly JobRepositoryInterface $jobRepository,
        private readonly CandidateProfileAdapter $candidateAdapter,
        private readonly JobRequirementsAdapter $jobAdapter,
        private readonly DeterministicMatcher $matcher,
        private readonly CanonicalHashService $hashService,
        private readonly ?GeminiExtractionAdapter $extractionAdapter = null,
        private readonly ?string $geminiModel = null
    ) {
    }

    /**
     * Performs the ownership and consent checks that must happen before a
     * stateful throttle is consumed. The analysis method repeats these checks
     * to remain safe when called outside the HTTP controller.
     *
     * @return 'allowed'|'declined_consent'|'revoked'
     */
    public function preflightAnalysis(array $user, string $applicationId): string
    {
        $application = $this->applicationRepository->findById($applicationId);
        if (!$application) {
            throw new NotFoundException("Không tìm thấy đơn ứng tuyển");
        }

        $this->authorizeStudentOwner($user, $application);

        if (!empty($application['ai_match_consent_revoked_at'])) {
            return 'revoked';
        }

        return empty($application['ai_match_consent']) ? 'declined_consent' : 'allowed';
    }

    /**
     * Executes or retrieves the match analysis for an application.
     * Guaranteed to be student-only, failure-isolated, and safe.
     *
     * @param array{id: string, role: string} $user Authenticated user session
     * @param string $applicationId Target application ID
     * @return array<string, mixed> Safe student DTO
     * @throws NotFoundException|AuthorizationException
     */
    public function analyze(array $user, string $applicationId): array
    {
        // 1. Fetch application and authorize student ownership
        $application = $this->applicationRepository->findById($applicationId);
        if (!$application) {
            throw new NotFoundException("Không tìm thấy đơn ứng tuyển");
        }

        $this->authorizeStudentOwner($user, $application);

        // 2. Strict per-application consent check
        if (!empty($application['ai_match_consent_revoked_at'])) {
            return [
                'status' => 'revoked',
                'failure_code' => 'CONSENT_REVOKED',
                'overall_score' => null,
                'coverage_percent' => null,
                'classification' => null,
                'criteria' => null,
                'summary' => null,
                'completed_at' => null,
            ];
        }

        if (empty($application['ai_match_consent'])) {
            return [
                'status' => 'declined_consent',
                'failure_code' => 'CONSENT_REQUIRED',
                'overall_score' => null,
                'coverage_percent' => null,
                'classification' => null,
                'criteria' => null,
                'summary' => null,
                'completed_at' => null,
            ];
        }

        // 3. Fetch candidate profile and target job
        $userId = (string)$user['id'];
        $profile = $this->profileRepository->findByUserId($userId);
        if (!$profile) {
            return [
                'status' => 'failed',
                'failure_code' => 'INSUFFICIENT_DATA',
                'overall_score' => null,
                'coverage_percent' => null,
                'classification' => null,
                'criteria' => null,
                'summary' => null,
                'completed_at' => null,
            ];
        }

        $jobId = (string)($application['job_id'] ?? '');
        $job = $this->jobRepository->findById($jobId);
        if (empty($job)) {
            throw new NotFoundException("Không tìm thấy thông tin việc làm");
        }

        // 4. Adapt structured data to canonical contracts
        $preferredShift = $application['preferred_shift'] ?? null;
        $candidateContract = $this->candidateAdapter->adapt($profile, $preferredShift, $applicationId);
        $jobContract = $this->jobAdapter->adapt($job);

        // 5. Compute cryptographic hashes & cache key
        $candidateHash = $this->hashService->computeCandidateHash($candidateContract);
        $jobHash = $this->hashService->computeJobHash($jobContract);
        $matcherVersion = DeterministicMatcher::MATCHER_VERSION;
        $promptVersion = GeminiExtractionAdapter::PROMPT_VERSION;
        $schemaVersion = GeminiExtractionAdapter::SCHEMA_VERSION;
        $model = $this->geminiModel ?? Config::geminiModel();

        $cacheKey = $this->hashService->computeCacheKey(
            $applicationId,
            $candidateHash,
            $jobHash,
            $matcherVersion,
            $promptVersion,
            $schemaVersion,
            $model,
            'profile'
        );

        // 6. Check exact-version cache and atomically acquire retry/expired work.
        $analysisId = null;
        $candidateExtraction = null;
        $jobExtraction = null;
        $cached = $this->analysisRepository->findByCacheKey($cacheKey);
        if ($cached !== null) {
            if ($cached->getStatus() === 'completed') {
                Logger::info("MatchAnalysis cache hit", [
                    'analysis_id' => $cached->getId(),
                    'application_id' => $applicationId,
                    'cache_hit' => true,
                    'status' => 'completed',
                    'duration_ms' => 0,
                    'model' => $model,
                    'matcher_version' => $matcherVersion,
                    'prompt_version' => $promptVersion,
                    'schema_version' => $schemaVersion,
                ]);
                return $cached->toSafeStudentDto();
            }

            if ($cached->getStatus() === 'revoked') {
                return $cached->toSafeStudentDto();
            }

            if ($cached->getStatus() === 'processing') {
                $startedAt = $this->timestampOrZero(
                    $cached->getStartedAt() ?? $cached->getUpdatedAt() ?? $cached->getCreatedAt()
                );
                if ($startedAt > 0 && (time() - $startedAt) < self::LEASE_DURATION_SECONDS) {
                    $this->logCacheState($cached, $applicationId, $model, 'processing_lease');
                    return $cached->toSafeStudentDto();
                }
            }

            if (in_array($cached->getStatus(), ['partial', 'failed'], true)) {
                $terminalAt = $this->timestampOrZero(
                    $cached->getCompletedAt() ?? $cached->getUpdatedAt() ?? $cached->getCreatedAt()
                );
                if ($terminalAt > 0 && (time() - $terminalAt) < self::COOLDOWN_SECONDS) {
                    $dto = $cached->toSafeStudentDto();
                    $dto['cooldown'] = true;
                    $dto['retry_after'] = max(1, self::COOLDOWN_SECONDS - (time() - $terminalAt));
                    $this->logCacheState($cached, $applicationId, $model, 'cooldown');
                    return $dto;
                }
            }

            if (in_array($cached->getStatus(), ['processing', 'partial', 'failed'], true)) {
                // Keep validated reusable fragments in memory before claimForProcessing
                // clears only the prior presentation/result fields.
                $candidateExtraction = $cached->getCandidateExtractionJson();
                $jobExtraction = $cached->getJobExtractionJson();
                $claimed = $this->analysisRepository->claimForProcessing(
                    $cached->getId(),
                    self::LEASE_DURATION_SECONDS,
                    self::COOLDOWN_SECONDS
                );

                if (!$claimed) {
                    $current = $this->analysisRepository->findByCacheKey($cacheKey);
                    $dto = ($current ?? $cached)->toSafeStudentDto();
                    $this->logCacheState($current ?? $cached, $applicationId, $model, 'claim_lost');
                    return $dto;
                }

                $analysisId = $cached->getId();
                Logger::info('MatchAnalysis work claimed', [
                    'analysis_id' => $analysisId,
                    'application_id' => $applicationId,
                    'previous_status' => $cached->getStatus(),
                    'cache_hit' => true,
                    'model' => $model,
                    'matcher_version' => $matcherVersion,
                    'prompt_version' => $promptVersion,
                    'schema_version' => $schemaVersion,
                ]);
            }
        }

        // 7. Create a new processing record only when this exact cache key has
        // never existed. The unique key handles first-request concurrency.
        if ($analysisId === null) {
            $analysis = JobMatchAnalysis::createProcessing(
                applicationId: $applicationId,
                candidateHash: $candidateHash,
                jobHash: $jobHash,
                cacheKey: $cacheKey,
                matcherVersion: $matcherVersion,
                promptVersion: $promptVersion,
                schemaVersion: $schemaVersion,
                geminiModel: $model,
                consentSnapshot: 1,
                candidateSnapshotJson: $candidateContract->toArray(),
                jobSnapshotJson: $jobContract->toArray()
            );
            try {
                $this->analysisRepository->create($analysis);
                $analysisId = $analysis->getId();
            } catch (\Throwable $e) {
                if ($this->isDuplicateKeyException($e)) {
                    $existing = $this->analysisRepository->findByCacheKey($cacheKey);
                    if ($existing !== null) {
                        $this->logCacheState($existing, $applicationId, $model, 'duplicate_key');
                        return $existing->toSafeStudentDto();
                    }
                }
                throw $e;
            }
        }

        // 8. Reuse validated fragment extractions where possible.
        if ($candidateExtraction === null) {
            $candidateExtraction = $this->analysisRepository->findReusableCandidateExtraction(
                $candidateHash,
                $promptVersion,
                $schemaVersion,
                $model
            );
        }

        if ($jobExtraction === null) {
            $jobExtraction = $this->analysisRepository->findReusableJobExtraction(
                $jobHash,
                $promptVersion,
                $schemaVersion,
                $model
            );
        }

        /*
         * The previous implementation attempted to INSERT after an expired
         * processing/failed/partial cache row. Because cache_key is unique,
         * that path could only return the same stale row forever. Acquisition
         * above deliberately reuses that row under an atomic lease.
         */

        // 9. Execute AI semantic extractions only if needed (failure-isolated)
        $aiFailureCode = null;
        $usageMetadata = null;
        $startTime = microtime(true);

        if ($candidateExtraction === null) {
            if ($this->extractionAdapter === null) {
                $aiFailureCode = 'AI_DISABLED';
            } else {
                try {
                    $candText = $this->prepareCandidateText($profile);
                    if ($candText !== '') {
                        $res = $this->extractionAdapter->extractCandidateProfile(
                            $candText,
                            $profile['full_name'] ?? ($profile['name'] ?? null),
                            $profile['date_of_birth'] ?? null
                        );
                        $candidateExtraction = $res['extraction'];
                        $usageMetadata = $this->mergeUsageMetadata($usageMetadata, $res['usage_metadata'] ?? null);
                    }
                } catch (GeminiTimeoutException $e) {
                    $aiFailureCode = 'AI_TIMEOUT';
                    $this->logExtractionFailure('candidate', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (GeminiRateLimitException $e) {
                    $aiFailureCode = 'AI_RATE_LIMITED';
                    $this->logExtractionFailure('candidate', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (GeminiServiceException $e) {
                    $aiFailureCode = 'AI_UNAVAILABLE';
                    $this->logExtractionFailure('candidate', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (ExtractionValidationException $e) {
                    $aiFailureCode = 'AI_INVALID_RESPONSE';
                    $this->logExtractionFailure('candidate', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (GeminiException $e) {
                    $aiFailureCode = 'AI_UNAVAILABLE';
                    $this->logExtractionFailure('candidate', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (\Throwable $e) {
                    $aiFailureCode = 'AI_UNAVAILABLE';
                    $this->logExtractionFailure('candidate', $aiFailureCode, $e, $analysisId, $applicationId, true);
                }
            }
        }

        if ($jobExtraction === null && $aiFailureCode === null) {
            if ($this->extractionAdapter === null) {
                $aiFailureCode = 'AI_DISABLED';
            } else {
                try {
                    $jobText = $this->prepareJobText($job);
                    if ($jobText !== '') {
                        $res = $this->extractionAdapter->extractJobRequirements($jobText);
                        $jobExtraction = $res['extraction'];
                        $usageMetadata = $this->mergeUsageMetadata($usageMetadata, $res['usage_metadata'] ?? null);
                    }
                } catch (GeminiTimeoutException $e) {
                    $aiFailureCode = 'AI_TIMEOUT';
                    $this->logExtractionFailure('job', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (GeminiRateLimitException $e) {
                    $aiFailureCode = 'AI_RATE_LIMITED';
                    $this->logExtractionFailure('job', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (GeminiServiceException $e) {
                    $aiFailureCode = 'AI_UNAVAILABLE';
                    $this->logExtractionFailure('job', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (ExtractionValidationException $e) {
                    $aiFailureCode = 'AI_INVALID_RESPONSE';
                    $this->logExtractionFailure('job', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (GeminiException $e) {
                    $aiFailureCode = 'AI_UNAVAILABLE';
                    $this->logExtractionFailure('job', $aiFailureCode, $e, $analysisId, $applicationId);
                } catch (\Throwable $e) {
                    $aiFailureCode = 'AI_UNAVAILABLE';
                    $this->logExtractionFailure('job', $aiFailureCode, $e, $analysisId, $applicationId, true);
                }
            }
        }

        $durationMs = (int)(round((microtime(true) - $startTime) * 1000));

        // 11. Merge structured DB data with validated semantic extractions
        $effectiveCandidate = $this->enrichCandidateContract($candidateContract, $candidateExtraction);
        $effectiveJob = $this->enrichJobContract($jobContract, $jobExtraction);

        // 12. Run Deterministic Matcher (PHP does all scoring)
        $matchResult = $this->matcher->match($effectiveCandidate, $effectiveJob);

        $criteriaData = array_map(fn($c) => $c->toArray(), $matchResult->criteria);
        $summaryData = [
            'strengths' => $matchResult->strengths,
            'considerations' => $matchResult->considerations,
            'missing_data' => $matchResult->missingData,
            'disclaimer' => $matchResult->disclaimer,
        ];

        // 13. Persist analysis outcome (Failure Isolation: applications.status is NEVER modified)
        // Anti-Race: Re-read application consent immediately before terminal write. Revoke must always win!
        $freshApp = $this->applicationRepository->findById($applicationId);
        if (!$freshApp || empty($freshApp['ai_match_consent']) || !empty($freshApp['ai_match_consent_revoked_at'])) {
            $this->analysisRepository->purgeByApplicationId($applicationId);
            Logger::info('MatchAnalysis discarded after consent revocation', [
                'analysis_id' => $analysisId,
                'application_id' => $applicationId,
                'duration_ms' => $durationMs,
                'model' => $model,
                'matcher_version' => $matcherVersion,
                'prompt_version' => $promptVersion,
                'schema_version' => $schemaVersion,
            ]);
            return [
                'status' => 'revoked',
                'failure_code' => 'CONSENT_REVOKED',
                'overall_score' => null,
                'coverage_percent' => null,
                'classification' => null,
                'criteria' => null,
                'summary' => null,
                'completed_at' => null,
            ];
        }

        if ($aiFailureCode !== null) {
            if ($matchResult->coveragePercent > 0.0) {
                $this->analysisRepository->markPartial($analysisId, [
                    'overall_score' => $matchResult->overallScore,
                    'coverage_percent' => $matchResult->coveragePercent,
                    'classification' => $matchResult->classification->value,
                    'criteria_json' => $criteriaData,
                    'summary_json' => $summaryData,
                    'candidate_extraction_json' => $candidateExtraction,
                    'job_extraction_json' => $jobExtraction,
                    'usage_metadata_json' => $usageMetadata,
                    'duration_ms' => $durationMs,
                    'failure_code' => $aiFailureCode,
                ]);
            } else {
                $this->analysisRepository->markFailed($analysisId, $aiFailureCode);
            }
        } else {
            $this->analysisRepository->complete($analysisId, [
                'overall_score' => $matchResult->overallScore,
                'coverage_percent' => $matchResult->coveragePercent,
                'classification' => $matchResult->classification->value,
                'criteria_json' => $criteriaData,
                'summary_json' => $summaryData,
                'candidate_extraction_json' => $candidateExtraction,
                'job_extraction_json' => $jobExtraction,
                'usage_metadata_json' => $usageMetadata,
                'duration_ms' => $durationMs,
            ]);
        }

        // Reload before logging: a concurrent revoke may have won the
        // conditional terminal write and must be reflected in telemetry/DTO.
        $updated = $this->analysisRepository->findById($analysisId);
        $persistedStatus = $updated?->getStatus() ?? 'failed';

        // Structured Logging (Never log raw text, CV, JD, prompts, secrets, or PII)
        Logger::info("MatchAnalysis finished execution", [
            'analysis_id' => $analysisId,
            'application_id' => $applicationId,
            'cache_hit' => $cached !== null,
            'terminal_status' => $persistedStatus,
            'failure_code' => $persistedStatus === 'revoked' ? 'CONSENT_REVOKED' : $aiFailureCode,
            'gemini_success' => $aiFailureCode === null,
            'duration_ms' => $durationMs,
            'model' => $model,
            'matcher_version' => $matcherVersion,
            'prompt_version' => $promptVersion,
            'schema_version' => $schemaVersion,
            'usage_metadata' => $usageMetadata,
        ]);

        // 14. Return fresh safe student DTO
        return $updated !== null ? $updated->toSafeStudentDto() : [
            'status' => 'failed',
            'failure_code' => $aiFailureCode ?? 'AI_UNAVAILABLE',
            'overall_score' => null,
            'coverage_percent' => null,
            'classification' => null,
            'criteria' => null,
            'summary' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Read-only retrieval of the match analysis state for an application.
     *
     * @param array{id: string, role: string} $user Authenticated user session
     * @param string $applicationId Target application ID
     * @return array<string, mixed> Safe student DTO
     * @throws NotFoundException|AuthorizationException
     */
    public function getAnalysis(array $user, string $applicationId): array
    {
        $application = $this->applicationRepository->findById($applicationId);
        if (!$application) {
            throw new NotFoundException("Không tìm thấy đơn ứng tuyển");
        }

        $this->authorizeStudentOwner($user, $application);

        if (!empty($application['ai_match_consent_revoked_at'])) {
            return [
                'status' => 'revoked',
                'failure_code' => 'CONSENT_REVOKED',
                'consent' => false,
                'overall_score' => null,
                'coverage_percent' => null,
                'classification' => null,
                'criteria' => null,
                'summary' => null,
                'completed_at' => null,
            ];
        }

        if (empty($application['ai_match_consent'])) {
            return [
                'status' => 'declined_consent',
                'failure_code' => 'CONSENT_REQUIRED',
                'consent' => false,
                'overall_score' => null,
                'coverage_percent' => null,
                'classification' => null,
                'criteria' => null,
                'summary' => null,
                'completed_at' => null,
            ];
        }

        $latest = $this->analysisRepository->findLatestByApplicationId($applicationId);
        if ($latest === null) {
            return [
                'status' => 'not_started',
                'consent' => true,
                'is_stale' => false,
                'overall_score' => null,
                'coverage_percent' => null,
                'classification' => null,
                'criteria' => null,
                'summary' => null,
                'completed_at' => null,
            ];
        }

        // Compare current candidate & job hashes, versions, and model
        $userId = (string)$user['id'];
        $profile = $this->profileRepository->findByUserId($userId);
        $jobId = (string)($application['job_id'] ?? '');
        $job = $this->jobRepository->findById($jobId);

        $isStale = !$profile || !$job;
        if ($profile && $job) {
            $preferredShift = $application['preferred_shift'] ?? null;
            $currentCandContract = $this->candidateAdapter->adapt($profile, $preferredShift, $applicationId);
            $currentJobContract = $this->jobAdapter->adapt($job);

            $currentCandHash = $this->hashService->computeCandidateHash($currentCandContract);
            $currentJobHash = $this->hashService->computeJobHash($currentJobContract);
            $currentMatcherVersion = DeterministicMatcher::MATCHER_VERSION;
            $currentPromptVersion = GeminiExtractionAdapter::PROMPT_VERSION;
            $currentSchemaVersion = GeminiExtractionAdapter::SCHEMA_VERSION;
            $currentModel = $this->geminiModel ?? Config::geminiModel();
            $currentCacheKey = $this->hashService->computeCacheKey(
                $applicationId,
                $currentCandHash,
                $currentJobHash,
                $currentMatcherVersion,
                $currentPromptVersion,
                $currentSchemaVersion,
                $currentModel,
                'profile'
            );
            $exactVersion = $this->analysisRepository->findByCacheKey($currentCacheKey);
            if ($exactVersion !== null) {
                // Prefer exact version identity over timestamp ordering. MVP
                // timestamps have second precision, so two versions can tie.
                $latest = $exactVersion;
                $isStale = false;
            } else {
                $isStale = (
                    $currentCandHash !== $latest->getCandidateHash()
                    || $currentJobHash !== $latest->getJobHash()
                    || $currentMatcherVersion !== $latest->getMatcherVersion()
                    || $currentPromptVersion !== $latest->getPromptVersion()
                    || $currentSchemaVersion !== $latest->getSchemaVersion()
                    || $currentModel !== $latest->getGeminiModel()
                );
            }
        }

        $dto = $latest->toSafeStudentDto();
        $dto['consent'] = true;
        $dto['is_stale'] = $isStale;
        if ($latest->getStatus() === 'processing') {
            $leaseStartedAt = $this->timestampOrZero(
                $latest->getStartedAt() ?? $latest->getUpdatedAt() ?? $latest->getCreatedAt()
            );
            if ($leaseStartedAt === 0 || (time() - $leaseStartedAt) >= self::LEASE_DURATION_SECONDS) {
                $dto['status'] = 'failed';
                $dto['failure_code'] = 'ANALYSIS_STALLED';
                $dto['is_stale'] = true;
                return $dto;
            }
        }

        if ($isStale && in_array($dto['status'], ['completed', 'partial'], true)) {
            $dto['status'] = 'stale';
        }
        return $dto;
    }

    /**
     * Revokes AI match analysis consent for an application.
     * Idempotent and purges candidate data across all analyses of this application.
     *
     * @param array{id: string, role: string} $user Authenticated user session
     * @param string $applicationId Target application ID
     * @return array{revoked: bool, application_id: string}
     * @throws NotFoundException|AuthorizationException
     */
    public function revokeConsent(array $user, string $applicationId): array
    {
        $application = $this->applicationRepository->findById($applicationId);
        if (!$application) {
            throw new NotFoundException("Không tìm thấy đơn ứng tuyển");
        }

        $this->authorizeStudentOwner($user, $application);

        // Update application consent metadata (idempotent)
        $now = date('Y-m-d H:i:s');
        $this->applicationRepository->updateConsent($applicationId, false, $now);

        // Purge ALL associated analyses for this application
        $this->analysisRepository->purgeByApplicationId($applicationId);

        return [
            'revoked' => true,
            'application_id' => $applicationId,
        ];
    }

    private function authorizeStudentOwner(array $user, array $application): void
    {
        $role = (string)($user['role'] ?? '');
        if (!in_array($role, ['student', 'developer'], true)) {
            throw new AuthorizationException("Chỉ ứng viên sở hữu đơn ứng tuyển mới có thể xem phân tích độ phù hợp");
        }

        $userId = (string)($user['id'] ?? '');
        $developerId = (string)($application['developer_id'] ?? ($application['student_user_id'] ?? ''));

        if ($developerId === '' || $developerId !== $userId) {
            throw new AuthorizationException("Bạn không có quyền truy cập đơn ứng tuyển này");
        }
    }

    private function prepareCandidateText(array $profile): string
    {
        $parts = [];
        if (!empty($profile['work_experience'])) {
            $parts[] = "Kinh nghiệm làm việc: " . trim((string)$profile['work_experience']);
        }
        if (!empty($profile['education'])) {
            $parts[] = "Học vấn: " . trim((string)$profile['education']);
        }
        if (!empty($profile['major'])) {
            $parts[] = "Chuyên ngành: " . trim((string)$profile['major']);
        }
        if (!empty($profile['certificates'])) {
            $parts[] = "Chứng chỉ: " . trim((string)$profile['certificates']);
        }
        if (!empty($profile['skills'])) {
            $skillsStr = is_array($profile['skills']) ? implode(', ', $profile['skills']) : (string)$profile['skills'];
            $parts[] = "Kỹ năng: " . trim($skillsStr);
        }
        return implode("\n", $parts);
    }

    private function prepareJobText(array $job): string
    {
        $parts = [];
        if (!empty($job['title'])) {
            $parts[] = "Tiêu đề: " . trim((string)$job['title']);
        }
        if (!empty($job['description'])) {
            $parts[] = "Mô tả công việc: " . trim((string)$job['description']);
        }
        if (!empty($job['requirements'])) {
            $parts[] = "Yêu cầu: " . trim((string)$job['requirements']);
        }
        if (!empty($job['required_skills'])) {
            $skillsStr = is_array($job['required_skills']) ? implode(', ', $job['required_skills']) : (string)$job['required_skills'];
            $parts[] = "Kỹ năng yêu cầu: " . trim($skillsStr);
        }
        return implode("\n", $parts);
    }

    private function enrichCandidateContract(
        CandidateProfileContract $contract,
        ?array $extraction
    ): CandidateProfileContract {
        if ($extraction === null) {
            return $contract;
        }

        // 1. Skills enrichment (structured skills are authoritative)
        $existingSkills = $contract->skills;
        $existingKeys = [];
        foreach ($existingSkills as $s) {
            $key = $s->matchKey ?? SkillNormalizer::generateMatchKey($s->canonicalName);
            $existingKeys[$key] = true;
        }

        $newSkills = $existingSkills;
        if (!empty($extraction['skills']) && is_array($extraction['skills'])) {
            foreach ($extraction['skills'] as $rawSkill) {
                if (empty($rawSkill['canonical_name']) || !is_string($rawSkill['canonical_name'])) {
                    continue;
                }
                $name = trim($rawSkill['canonical_name']);
                if (PiiRedactor::containsPlaceholder($name)) {
                    continue;
                }
                $key = SkillNormalizer::generateMatchKey($name);
                if (!isset($existingKeys[$key])) {
                    $existingKeys[$key] = true;
                    $newSkills[] = new SkillItem(
                        canonicalName: $name,
                        sourceSkillId: null,
                        matchKey: $key,
                        evidence: isset($rawSkill['evidence']) ? (string)$rawSkill['evidence'] : null,
                        confidence: isset($rawSkill['confidence']) && is_numeric($rawSkill['confidence']) ? (float)$rawSkill['confidence'] : 0.8,
                        provenance: Provenance::SEMANTIC_EXTRACTION,
                        importance: 'preferred'
                    );
                }
            }
        }

        // 2. Experience enrichment
        $effectiveExperience = $contract->experience;
        if (!empty($extraction['experience']) && is_array($extraction['experience'])) {
            $expItems = [];
            foreach ($extraction['experience'] as $rawExp) {
                if (empty($rawExp['role']) || !is_string($rawExp['role'])) {
                    continue;
                }
                $role = trim($rawExp['role']);
                if (PiiRedactor::containsPlaceholder($role)) {
                    continue;
                }
                $expItems[] = new ExperienceItem(
                    role: $role,
                    durationMonths: isset($rawExp['duration_months']) && is_numeric($rawExp['duration_months']) ? (int)$rawExp['duration_months'] : null,
                    evidence: isset($rawExp['evidence']) ? (string)$rawExp['evidence'] : null,
                    confidence: isset($rawExp['confidence']) && is_numeric($rawExp['confidence']) ? (float)$rawExp['confidence'] : 0.85,
                    provenance: Provenance::SEMANTIC_EXTRACTION
                );
            }
            if (!empty($expItems)) {
                $existingItems = $effectiveExperience?->items ?? [];
                $existingHasDuration = false;
                foreach ($existingItems as $existingItem) {
                    if ($existingItem->durationMonths !== null) {
                        $existingHasDuration = true;
                        break;
                    }
                }
                // The current profile column is free text/JSON text. Its coarse
                // adapter item has no duration, so a source-backed extraction is
                // more useful. Truly structured duration data remains authoritative.
                if ($effectiveExperience === null
                    || $effectiveExperience->state === DataState::UNKNOWN
                    || empty($existingItems)
                    || !$existingHasDuration
                ) {
                    $effectiveExperience = new ExperienceValue(
                        state: DataState::AVAILABLE,
                        items: $expItems
                    );
                }
            }
        }

        // 3. Education enrichment
        $effectiveEducation = $contract->education;
        if (
            ($effectiveEducation === null
                || $effectiveEducation->state === DataState::UNKNOWN
                || $effectiveEducation->major === null)
            && isset($extraction['education'])
            && is_array($extraction['education'])
            && ($extraction['education']['state'] ?? '') === 'AVAILABLE'
        ) {
            $major = $effectiveEducation?->major
                ?? (!empty($extraction['education']['major']) ? trim((string)$extraction['education']['major']) : null);
            if ($major !== null && !PiiRedactor::containsPlaceholder($major)) {
                $effectiveEducation = new EducationValue(
                    state: DataState::AVAILABLE,
                    major: $major,
                    academicYear: $effectiveEducation?->academicYear,
                    level: $effectiveEducation?->level
                        ?? (!empty($extraction['education']['level']) ? (string)$extraction['education']['level'] : null),
                    evidence: isset($extraction['education']['evidence']) ? (string)$extraction['education']['evidence'] : null,
                    confidence: isset($extraction['education']['confidence']) && is_numeric($extraction['education']['confidence']) ? (float)$extraction['education']['confidence'] : 0.85
                );
            }
        }

        return new CandidateProfileContract(
            schemaVersion: $contract->schemaVersion,
            sourceType: $contract->sourceType,
            sourceProfileId: $contract->sourceProfileId,
            sourceApplicationId: $contract->sourceApplicationId,
            skills: $newSkills,
            availability: $contract->availability,
            experience: $effectiveExperience,
            education: $effectiveEducation,
            projects: $contract->projects,
            certifications: $contract->certifications,
            locations: $contract->locations,
            desiredRoles: $contract->desiredRoles,
            salaryExpectation: $contract->salaryExpectation
        );
    }

    private function enrichJobContract(
        JobRequirementsContract $contract,
        ?array $extraction
    ): JobRequirementsContract {
        if ($extraction === null) {
            return $contract;
        }

        // 1. Skills enrichment (structured skills are authoritative)
        $existingSkills = $contract->skills;
        $existingKeys = [];
        foreach ($existingSkills as $s) {
            $key = $s->matchKey ?? SkillNormalizer::generateMatchKey($s->canonicalName);
            $existingKeys[$key] = true;
        }

        $newSkills = $existingSkills;
        if (!empty($extraction['skills']) && is_array($extraction['skills'])) {
            foreach ($extraction['skills'] as $rawSkill) {
                if (empty($rawSkill['canonical_name']) || !is_string($rawSkill['canonical_name'])) {
                    continue;
                }
                $name = trim($rawSkill['canonical_name']);
                if (PiiRedactor::containsPlaceholder($name)) {
                    continue;
                }
                $key = SkillNormalizer::generateMatchKey($name);
                if (!isset($existingKeys[$key])) {
                    $existingKeys[$key] = true;
                    $importance = ($rawSkill['importance'] ?? 'preferred') === 'required' ? 'required' : 'preferred';
                    $newSkills[] = new SkillItem(
                        canonicalName: $name,
                        sourceSkillId: null,
                        matchKey: $key,
                        evidence: isset($rawSkill['evidence']) ? (string)$rawSkill['evidence'] : null,
                        confidence: isset($rawSkill['confidence']) && is_numeric($rawSkill['confidence']) ? (float)$rawSkill['confidence'] : 0.8,
                        provenance: Provenance::SEMANTIC_EXTRACTION,
                        importance: $importance
                    );
                }
            }
        }

        // 2. Experience requirement enrichment
        $effectiveExp = $contract->experienceRequirement;
        if (($effectiveExp === null || $effectiveExp->state === DataState::UNKNOWN)
            && isset($extraction['experience_requirement'])
            && is_array($extraction['experience_requirement'])
        ) {
            $extractedState = $extraction['experience_requirement']['state'] ?? 'UNKNOWN';
            $minMonths = isset($extraction['experience_requirement']['minimum_months']) && is_numeric($extraction['experience_requirement']['minimum_months'])
                ? (int)$extraction['experience_requirement']['minimum_months']
                : null;
            $domains = isset($extraction['experience_requirement']['domains']) && is_array($extraction['experience_requirement']['domains'])
                ? array_values(array_filter($extraction['experience_requirement']['domains'], 'is_string'))
                : [];
            $evidence = isset($extraction['experience_requirement']['evidence']) ? (string)$extraction['experience_requirement']['evidence'] : null;

            if ($extractedState === DataState::NOT_APPLICABLE->value) {
                $effectiveExp = new ExperienceRequirementValue(
                    state: DataState::NOT_APPLICABLE,
                    minimumMonths: null,
                    domains: [],
                    evidence: $evidence,
                    confidence: isset($extraction['experience_requirement']['confidence'])
                        ? (float)$extraction['experience_requirement']['confidence']
                        : 0.85
                );
            } elseif ($extractedState === DataState::AVAILABLE->value
                && ($minMonths !== null || !empty($domains) || !empty($evidence))
            ) {
                $effectiveExp = new ExperienceRequirementValue(
                    state: DataState::AVAILABLE,
                    minimumMonths: $minMonths,
                    domains: $domains,
                    evidence: $evidence,
                    confidence: isset($extraction['experience_requirement']['confidence']) && is_numeric($extraction['experience_requirement']['confidence']) ? (float)$extraction['experience_requirement']['confidence'] : 0.85
                );
            }
        }

        // 3. Education requirement enrichment
        $effectiveEdu = $contract->educationRequirement;
        if (
            ($effectiveEdu === null || $effectiveEdu->state === DataState::UNKNOWN)
            && isset($extraction['education_requirement'])
            && is_array($extraction['education_requirement'])
            && ($extraction['education_requirement']['state'] ?? '') === 'AVAILABLE'
        ) {
            $levels = isset($extraction['education_requirement']['levels']) && is_array($extraction['education_requirement']['levels'])
                ? array_values(array_filter($extraction['education_requirement']['levels'], 'is_string'))
                : [];
            $majors = isset($extraction['education_requirement']['majors']) && is_array($extraction['education_requirement']['majors'])
                ? array_values(array_filter($extraction['education_requirement']['majors'], 'is_string'))
                : [];
            $evidence = isset($extraction['education_requirement']['evidence']) ? (string)$extraction['education_requirement']['evidence'] : null;

            if (!empty($levels) || !empty($majors) || !empty($evidence)) {
                $effectiveEdu = new EducationRequirementValue(
                    state: DataState::AVAILABLE,
                    levels: $levels,
                    majors: $majors,
                    evidence: $evidence,
                    confidence: isset($extraction['education_requirement']['confidence']) && is_numeric($extraction['education_requirement']['confidence']) ? (float)$extraction['education_requirement']['confidence'] : 0.85
                );
            }
        }

        // 4. Schedule enrichment. Candidate availability remains strictly
        // structured profile/application data; AI may only normalize the job
        // shift when the structured job schedule is UNKNOWN.
        $effectiveSchedule = $contract->schedule;
        if (($effectiveSchedule === null || $effectiveSchedule->state === DataState::UNKNOWN)
            && isset($extraction['schedule_requirement'])
            && is_array($extraction['schedule_requirement'])
            && ($extraction['schedule_requirement']['state'] ?? '') === DataState::AVAILABLE->value
        ) {
            $shift = isset($extraction['schedule_requirement']['shift_type'])
                && is_string($extraction['schedule_requirement']['shift_type'])
                ? ShiftType::tryFrom($extraction['schedule_requirement']['shift_type'])
                : null;
            if ($shift !== null) {
                $effectiveSchedule = new ScheduleRequirementValue(
                    state: DataState::AVAILABLE,
                    shiftType: $shift,
                    slots: [],
                    minimumShiftsPerWeek: isset($extraction['schedule_requirement']['minimum_shifts_per_week'])
                        && is_int($extraction['schedule_requirement']['minimum_shifts_per_week'])
                        ? $extraction['schedule_requirement']['minimum_shifts_per_week']
                        : null,
                    evidence: isset($extraction['schedule_requirement']['evidence'])
                        ? (string)$extraction['schedule_requirement']['evidence']
                        : null,
                    confidence: isset($extraction['schedule_requirement']['confidence'])
                        ? (float)$extraction['schedule_requirement']['confidence']
                        : 0.85
                );
            }
        }

        return new JobRequirementsContract(
            schemaVersion: $contract->schemaVersion,
            sourceJobId: $contract->sourceJobId,
            role: $contract->role,
            skills: $newSkills,
            experienceRequirement: $effectiveExp,
            educationRequirement: $effectiveEdu,
            schedule: $effectiveSchedule,
            location: $contract->location,
            salary: $contract->salary,
            applicationState: $contract->applicationState
        );
    }

    private function mergeUsageMetadata(?array $existing, ?array $new): ?array
    {
        $existing = $this->sanitizeUsageMetadata($existing);
        $new = $this->sanitizeUsageMetadata($new);
        if ($existing === null) {
            return $new;
        }
        if ($new === null) {
            return $existing;
        }
        return [
            'promptTokenCount' => ($existing['promptTokenCount'] ?? 0) + ($new['promptTokenCount'] ?? 0),
            'candidatesTokenCount' => ($existing['candidatesTokenCount'] ?? 0) + ($new['candidatesTokenCount'] ?? 0),
            'totalTokenCount' => ($existing['totalTokenCount'] ?? 0) + ($new['totalTokenCount'] ?? 0),
        ];
    }

    /** @return array<string, int>|null */
    private function sanitizeUsageMetadata(?array $metadata): ?array
    {
        if ($metadata === null) {
            return null;
        }

        $safe = [];
        foreach (['promptTokenCount', 'candidatesTokenCount', 'totalTokenCount'] as $key) {
            $value = $metadata[$key] ?? null;
            if (is_int($value) && $value >= 0) {
                $safe[$key] = min($value, 10000000);
            }
        }

        return $safe === [] ? null : $safe;
    }

    private function timestampOrZero(?string $value): int
    {
        if ($value === null || trim($value) === '') {
            return 0;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
    }

    private function isDuplicateKeyException(\Throwable $e): bool
    {
        return $e instanceof \PDOException
            && (
                $e->getCode() === '23000'
                || str_contains($e->getMessage(), 'Duplicate entry')
                || (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062)
            );
    }

    private function logCacheState(
        JobMatchAnalysis $analysis,
        string $applicationId,
        ?string $model,
        string $cacheState
    ): void {
        Logger::info('MatchAnalysis cache state', [
            'analysis_id' => $analysis->getId(),
            'application_id' => $applicationId,
            'cache_hit' => true,
            'cache_state' => $cacheState,
            'status' => $analysis->getStatus(),
            'model' => $model,
            'matcher_version' => $analysis->getMatcherVersion(),
            'prompt_version' => $analysis->getPromptVersion(),
            'schema_version' => $analysis->getSchemaVersion(),
        ]);
    }

    private function logExtractionFailure(
        string $stage,
        string $failureCode,
        \Throwable $exception,
        string $analysisId,
        string $applicationId,
        bool $unexpected = false
    ): void {
        $context = [
            'analysis_id' => $analysisId,
            'application_id' => $applicationId,
            'stage' => $stage,
            'failure_code' => $failureCode,
            'exception_class' => $exception::class,
        ];
        if ($unexpected) {
            Logger::error('MatchAnalysis extraction failed unexpectedly', $context);
            return;
        }
        Logger::warning('MatchAnalysis extraction failed', $context);
    }
}
