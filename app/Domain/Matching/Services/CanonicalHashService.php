<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services;

use JobMarket\Domain\Matching\Contracts\CandidateProfileContract;
use JobMarket\Domain\Matching\Contracts\JobRequirementsContract;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JobMarket\Facades\Config;

final class CanonicalHashService
{
    private string $secretKey;

    public function __construct(?string $secretKey = null)
    {
        if ($secretKey !== null && $secretKey !== '') {
            if (strlen($secretKey) < 32) {
                throw new \InvalidArgumentException('Provided HMAC secret key is too weak (minimum 32 characters required).');
            }
            $this->secretKey = $secretKey;
        } else {
            $configured = Config::aiMatchHashKey();
            if ($configured === null || strlen($configured) < 32) {
                throw new \RuntimeException(
                    'AI_MATCH_HASH_KEY is not configured in environment or is too weak (minimum 32 characters required).'
                );
            }
            $this->secretKey = $configured;
        }
    }

    /**
     * Computes HMAC-SHA256 hash for a candidate matching contract.
     * Guaranteed to be redacted, free of PII, and permutation-stable for unordered collections.
     */
    public function computeCandidateHash(CandidateProfileContract $contract): string
    {
        $arr = $contract->toArray();
        ValidationGuard::assertNoPii($arr, 'candidate_hash_input');

        // Permutation stabilization for collections representing sets
        $skills = $arr['skills'] ?? [];
        usort($skills, function(array $a, array $b): int {
            $keyA = $a['match_key'] ?? ($a['canonical_name'] ?? ($a['source_skill_id'] ?? ''));
            $keyB = $b['match_key'] ?? ($b['canonical_name'] ?? ($b['source_skill_id'] ?? ''));
            return strcmp((string)$keyA, (string)$keyB);
        });

        $availability = $arr['availability'] ?? [];
        if (isset($availability['slots']) && is_array($availability['slots'])) {
            $slots = $availability['slots'];
            usort($slots, function(array $a, array $b): int {
                $dayA = $a['day'] ?? '';
                $dayB = $b['day'] ?? '';
                $shiftA = $a['shift'] ?? '';
                $shiftB = $b['shift'] ?? '';
                return strcmp("{$dayA}_{$shiftA}", "{$dayB}_{$shiftB}");
            });
            $availability['slots'] = $slots;
        }

        $locations = $arr['locations'] ?? [];
        if (isset($locations['location_ids']) && is_array($locations['location_ids'])) {
            $locIds = $locations['location_ids'];
            sort($locIds, SORT_STRING);
            $locations['location_ids'] = array_values($locIds);
        }
        if (isset($locations['names']) && is_array($locations['names'])) {
            $locNames = $locations['names'];
            sort($locNames, SORT_STRING);
            $locations['names'] = array_values($locNames);
        }

        $matchingPayload = [
            'schema_version' => $arr['schema_version'],
            'skills' => $skills,
            'availability' => $availability,
            'experience' => $arr['experience'],
            'education' => $arr['education'],
            'projects' => $arr['projects'],
            'certifications' => $arr['certifications'],
            'locations' => $locations,
            'desired_roles' => $arr['desired_roles'],
            'salary_expectation' => $arr['salary_expectation'],
        ];

        $canonicalJson = $this->canonicalizeJson($matchingPayload);
        return hash_hmac('sha256', $canonicalJson, $this->secretKey);
    }

    /**
     * Computes HMAC-SHA256 hash for a job requirements contract.
     * Permutation-stable for unordered collections.
     */
    public function computeJobHash(JobRequirementsContract $contract): string
    {
        $arr = $contract->toArray();
        ValidationGuard::assertNoPii($arr, 'job_hash_input');

        $jobSkills = $arr['skills'] ?? [];
        usort($jobSkills, function(array $a, array $b): int {
            $keyA = $a['match_key'] ?? ($a['canonical_name'] ?? ($a['source_skill_id'] ?? ''));
            $keyB = $b['match_key'] ?? ($b['canonical_name'] ?? ($b['source_skill_id'] ?? ''));
            return strcmp((string)$keyA, (string)$keyB);
        });

        $schedule = $arr['schedule'] ?? [];
        if (isset($schedule['slots']) && is_array($schedule['slots'])) {
            $slots = $schedule['slots'];
            usort($slots, function(array $a, array $b): int {
                $dayA = $a['day'] ?? '';
                $dayB = $b['day'] ?? '';
                $shiftA = $a['shift'] ?? '';
                $shiftB = $b['shift'] ?? '';
                return strcmp("{$dayA}_{$shiftA}", "{$dayB}_{$shiftB}");
            });
            $schedule['slots'] = $slots;
        }

        $matchingPayload = [
            'schema_version' => $arr['schema_version'],
            'role' => $arr['role'],
            'skills' => $jobSkills,
            'experience_requirement' => $arr['experience_requirement'],
            'education_requirement' => $arr['education_requirement'],
            'schedule' => $schedule,
            'location' => $arr['location'],
            'salary' => $arr['salary'],
            'application_state' => $arr['application_state'],
        ];

        $canonicalJson = $this->canonicalizeJson($matchingPayload);
        return hash_hmac('sha256', $canonicalJson, $this->secretKey);
    }

    /**
     * Computes composite cache key for analysis deduplication:
     * application_id + candidate_hash + job_hash + matcher_version + prompt_version + schema_version + model + source
     */
    public function computeCacheKey(
        string $applicationId,
        string $candidateHash,
        string $jobHash,
        string $matcherVersion,
        string $promptVersion,
        string $schemaVersion,
        ?string $geminiModel = null,
        string $candidateSource = 'profile'
    ): string {
        $compositePayload = [
            'application_id' => $applicationId,
            'candidate_hash' => $candidateHash,
            'job_hash' => $jobHash,
            'matcher_version' => $matcherVersion,
            'prompt_version' => $promptVersion,
            'schema_version' => $schemaVersion,
            'gemini_model' => $geminiModel ?? 'none',
            'candidate_source' => $candidateSource,
        ];

        $canonicalJson = $this->canonicalizeJson($compositePayload);
        return hash_hmac('sha256', $canonicalJson, $this->secretKey);
    }

    /**
     * Recursively normalizes and encodes data to a deterministic, stable JSON string.
     * - Object keys are sorted alphabetically.
     * - Strings are trimmed and normalized.
     * - Uses JSON_THROW_ON_ERROR for fail-safe encoding.
     *
     * @param array<string, mixed> $data
     */
    public function canonicalizeJson(array $data): string
    {
        $normalized = $this->normalizeArrayRecursively($data);
        return json_encode($normalized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function normalizeArrayRecursively(mixed $data): mixed
    {
        if (!is_array($data)) {
            if (is_string($data)) {
                return trim($data);
            }
            return $data;
        }

        $isAssoc = $this->isAssociativeArray($data);

        if ($isAssoc) {
            ksort($data, SORT_STRING);
            $result = [];
            foreach ($data as $k => $v) {
                $result[(string)$k] = $this->normalizeArrayRecursively($v);
            }
            return $result;
        }

        // Sequential list
        $result = [];
        foreach ($data as $item) {
            $result[] = $this->normalizeArrayRecursively($item);
        }
        return $result;
    }

    /**
     * @param array<mixed> $arr
     */
    private function isAssociativeArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

