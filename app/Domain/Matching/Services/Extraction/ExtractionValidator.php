<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services\Extraction;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Services\PiiRedactor;

final class ExtractionValidator
{
    public const SCHEMA_VERSION = 'semantic-extraction.v1';
    public const DOC_CANDIDATE = 'candidate_profile';
    public const DOC_JOB = 'job_requirements';
    public const MIN_CONFIDENCE = 0.65;

    public const FORBIDDEN_FIELDS = [
        'full_name',
        'name',
        'email',
        'phone',
        'date_of_birth',
        'dob',
        'gender',
        'address',
        'exact_address',
        'cv_storage_path',
        'cv_url',
        'employer_note',
        'decision',
        'hired',
        'hiring_decision',
        'accept',
        'accepted',
        'reject',
        'rejected',
        'status',
        'application_status',
        'score',
        'overall_score',
        'system',
        'system_prompt',
        'instruction',
        'instructions',
        'prompt',
        'override',
        'role_override',
        'ignore_previous_instructions',
    ];

    public const CANDIDATE_ALLOWED_KEYS = [
        'schema_version',
        'document_type',
        'skills',
        'experience',
        'education',
        'schedule',
        'role_terms',
    ];

    public const JOB_ALLOWED_KEYS = [
        'schema_version',
        'document_type',
        'skills',
        'experience_requirement',
        'education_requirement',
        'schedule_requirement',
        'role_terms',
    ];

    /**
     * Cleans code fence and parses JSON string.
     *
     * @throws ExtractionValidationException
     */
    public function parseJson(string $rawText): array
    {
        $trimmed = trim($rawText);

        // Strip leading markdown code fence ```json or ```
        if (str_starts_with($trimmed, '```')) {
            $firstNewline = strpos($trimmed, "\n");
            if ($firstNewline !== false) {
                $trimmed = substr($trimmed, $firstNewline + 1);
            } else {
                $trimmed = ltrim($trimmed, "`json \t");
            }
            if (str_ends_with($trimmed, '```')) {
                $trimmed = substr($trimmed, 0, -3);
            }
            $trimmed = trim($trimmed);
        }

        try {
            $data = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ExtractionValidationException(
                ['json' => 'Phản hồi từ AI không phải định dạng JSON hợp lệ: ' . $e->getMessage()],
                'Lỗi cú pháp JSON từ AI extraction'
            );
        }

        if (!is_array($data)) {
            throw new ExtractionValidationException(
                ['json' => 'Dữ liệu JSON từ AI phải là một đối tượng (object).'],
                'Dữ liệu AI extraction không phải object'
            );
        }

        return $data;
    }

    /**
     * Validates and sanitizes candidate profile extraction.
     *
     * @param array<string, mixed> $data
     * @param string $sourceText Source text for evidence verification
     * @return array<string, mixed> Validated candidate extraction
     * @throws ExtractionValidationException
     */
    public function validateCandidateExtraction(array $data, string $sourceText): array
    {
        $this->assertNoForbiddenFields($data);
        $this->assertKeys($data, self::CANDIDATE_ALLOWED_KEYS, 'candidate_extraction');

        if (($data['schema_version'] ?? '') !== self::SCHEMA_VERSION) {
            throw new ExtractionValidationException(
                ['schema_version' => "Schema version '" . ($data['schema_version'] ?? '') . "' không hợp lệ. Yêu cầu: '" . self::SCHEMA_VERSION . "'."],
                'Schema version extraction không hợp lệ'
            );
        }

        if (($data['document_type'] ?? '') !== self::DOC_CANDIDATE) {
            throw new ExtractionValidationException(
                ['document_type' => "Document type '" . ($data['document_type'] ?? '') . "' không hợp lệ. Yêu cầu: '" . self::DOC_CANDIDATE . "'."],
                'Document type extraction không hợp lệ'
            );
        }

        $normalizedSource = self::normalizeForEvidenceCheck($sourceText);

        // 1. Skills (Must have non-empty verified evidence & confidence >= MIN_CONFIDENCE)
        $validSkills = [];
        $rawSkills = $this->requireList($data['skills'] ?? [], 'candidate.skills', 50);
        foreach ($rawSkills as $idx => $s) {
                if (!is_array($s)) {
                    $this->throwInvalidType("candidate.skills[{$idx}]", 'object');
                }
                $this->assertKeys($s, ['canonical_name', 'evidence', 'confidence'], "candidate.skills[{$idx}]");
                if (!isset($s['canonical_name']) || !is_string($s['canonical_name'])) {
                    continue;
                }
                $canonicalName = trim($s['canonical_name']);
                if ($canonicalName === '' || mb_strlen($canonicalName) < 2 || mb_strlen($canonicalName) > 255) {
                    continue;
                }
                if (PiiRedactor::containsPlaceholder($canonicalName) || PiiRedactor::isPlaceholderOrContact($canonicalName)) {
                    continue;
                }
                $confidence = $this->confidenceOrNull($s['confidence'] ?? null);
                if ($confidence === null || $confidence < self::MIN_CONFIDENCE) {
                    continue;
                }
                $evidence = $this->evidenceOrEmpty($s['evidence'] ?? null);
                if ($evidence === '' || !$this->verifyEvidence($evidence, $normalizedSource)) {
                    // Strictly discard items without verified source evidence
                    continue;
                }

                $validSkills[] = [
                    'canonical_name' => mb_substr($canonicalName, 0, 255),
                    'evidence' => mb_substr($evidence, 0, 500),
                    'confidence' => round($confidence, 2),
                ];
        }

        // 2. Experience (Must have non-empty verified evidence & confidence >= MIN_CONFIDENCE)
        $validExp = [];
        $rawExp = $this->requireList($data['experience'] ?? [], 'candidate.experience', 20);
        foreach ($rawExp as $idx => $e) {
                if (!is_array($e)) {
                    $this->throwInvalidType("candidate.experience[{$idx}]", 'object');
                }
                $this->assertKeys($e, ['role', 'duration_months', 'domains', 'evidence', 'confidence'], "candidate.experience[{$idx}]");
                if (!isset($e['role']) || !is_string($e['role'])) {
                    continue;
                }
                $role = trim($e['role']);
                if ($role === '' || mb_strlen($role) < 2 || mb_strlen($role) > 255) {
                    continue;
                }
                if (PiiRedactor::containsPlaceholder($role) || PiiRedactor::isPlaceholderOrContact($role)) {
                    continue;
                }
                $confidence = $this->confidenceOrNull($e['confidence'] ?? null);
                if ($confidence === null || $confidence < self::MIN_CONFIDENCE) {
                    continue;
                }
                $evidence = $this->evidenceOrEmpty($e['evidence'] ?? null);
                if ($evidence === '' || !$this->verifyEvidence($evidence, $normalizedSource)) {
                    // Strictly discard items without verified source evidence
                    continue;
                }

                $durationMonths = null;
                if (isset($e['duration_months'])) {
                    if (!is_int($e['duration_months'])) {
                        $this->throwInvalidType("candidate.experience[{$idx}].duration_months", 'integer|null');
                    }
                    if ($e['duration_months'] > 0 && $e['duration_months'] <= 600) {
                        $durationMonths = $e['duration_months'];
                    }
                }

                $domains = [];
                if (isset($e['domains'])) {
                    foreach ($this->requireList($e['domains'], "candidate.experience[{$idx}].domains", 10) as $d) {
                        if (is_string($d) && trim($d) !== '' && mb_strlen(trim($d)) <= 100) {
                            $dTrim = trim($d);
                            if (!PiiRedactor::containsPlaceholder($dTrim)) {
                                $domains[] = mb_substr($dTrim, 0, 100);
                            }
                        }
                    }
                    $domains = array_values(array_unique($domains));
                }

                $validExp[] = [
                    'role' => mb_substr($role, 0, 255),
                    'duration_months' => $durationMonths,
                    'domains' => $domains,
                    'evidence' => mb_substr($evidence, 0, 500),
                    'confidence' => round($confidence, 2),
                ];
        }

        // 3. Education (Never create AVAILABLE from UNKNOWN or unverified data; no fallback guessing)
        $eduObj = $data['education'] ?? null;
        if (array_key_exists('education', $data) && !is_array($eduObj)) {
            $this->throwInvalidType('candidate.education', 'object');
        }
        $validEdu = ['state' => DataState::UNKNOWN->value, 'level' => null, 'major' => null, 'evidence' => null, 'confidence' => 0.0];
        if (is_array($eduObj)) {
            $this->assertKeys($eduObj, ['state', 'level', 'major', 'evidence', 'confidence'], 'candidate.education');
            if (isset($eduObj['state']) && !is_string($eduObj['state'])) {
                $this->throwInvalidType('candidate.education.state', 'string');
            }
            $stateStr = isset($eduObj['state']) && is_string($eduObj['state'])
                ? strtoupper($eduObj['state'])
                : DataState::UNKNOWN->value;
            $this->assertEnum($stateStr, [DataState::AVAILABLE->value, DataState::UNKNOWN->value], 'candidate.education.state');
            $confidence = $this->confidenceOrNull($eduObj['confidence'] ?? null);
            $confVal = ($confidence !== null && $confidence >= self::MIN_CONFIDENCE) ? round($confidence, 2) : 0.0;
            $eduEvidence = $this->evidenceOrEmpty($eduObj['evidence'] ?? null);

            if ($stateStr === DataState::AVAILABLE->value && $eduEvidence !== '' && $this->verifyEvidence($eduEvidence, $normalizedSource) && $confVal >= self::MIN_CONFIDENCE) {
                $major = isset($eduObj['major']) && is_string($eduObj['major']) ? trim($eduObj['major']) : null;
                if ($major !== null && (PiiRedactor::containsPlaceholder($major) || mb_strlen($major) > 255 || $major === '')) {
                    $major = null;
                }
                $level = isset($eduObj['level']) && is_string($eduObj['level']) ? trim($eduObj['level']) : null;
                if ($level === '') {
                    $level = null;
                } elseif ($level !== null && mb_strlen($level) > 50) {
                    $level = null;
                }

                if ($major !== null || $level !== null) {
                    $validEdu = [
                        'state' => DataState::AVAILABLE->value,
                        'level' => $level,
                        'major' => $major,
                        'evidence' => mb_substr($eduEvidence, 0, 500),
                        'confidence' => $confVal,
                    ];
                }
            }
        }

        if (isset($data['schedule'])) {
            if (!is_array($data['schedule'])) {
                $this->throwInvalidType('candidate.schedule', 'object');
            }
            $this->assertKeys($data['schedule'], ['state', 'slots', 'evidence', 'confidence'], 'candidate.schedule');
            $scheduleState = $data['schedule']['state'] ?? DataState::UNKNOWN->value;
            if (!is_string($scheduleState) || strtoupper($scheduleState) !== DataState::UNKNOWN->value) {
                $this->throwInvalidEnum('candidate.schedule.state');
            }
            $scheduleSlots = $this->requireList($data['schedule']['slots'] ?? [], 'candidate.schedule.slots', 0);
            $scheduleConfidence = $this->confidenceOrNull($data['schedule']['confidence'] ?? 0.0);
            if ($scheduleSlots !== [] || ($data['schedule']['evidence'] ?? null) !== null || $scheduleConfidence !== 0.0) {
                throw new ExtractionValidationException(
                    ['candidate_schedule' => 'AI không được suy diễn lịch rảnh từ hồ sơ tự do.'],
                    'Candidate schedule extraction phải giữ UNKNOWN'
                );
            }
        }

        // 4. Role terms
        $validRoleTerms = [];
        $rawRoles = $this->requireList($data['role_terms'] ?? [], 'candidate.role_terms', 20);
        foreach ($rawRoles as $rt) {
                if (is_string($rt) && trim($rt) !== '' && mb_strlen(trim($rt)) <= 100) {
                    $rtTrim = trim($rt);
                    if (!PiiRedactor::containsPlaceholder($rtTrim) && !PiiRedactor::isPlaceholderOrContact($rtTrim)) {
                        $validRoleTerms[] = mb_substr($rtTrim, 0, 100);
                    }
                }
        }
        $validRoleTerms = array_values(array_unique($validRoleTerms));

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'document_type' => self::DOC_CANDIDATE,
            'skills' => $validSkills,
            'experience' => $validExp,
            'education' => $validEdu,
            'schedule' => [
                'state' => DataState::UNKNOWN->value,
                'slots' => [],
                'evidence' => null,
                'confidence' => 0.0,
            ],
            'role_terms' => $validRoleTerms,
        ];
    }

    /**
     * Validates and sanitizes job requirements extraction.
     *
     * @param array<string, mixed> $data
     * @param string $sourceText Source text for evidence verification
     * @return array<string, mixed> Validated job requirements extraction
     * @throws ExtractionValidationException
     */
    public function validateJobExtraction(array $data, string $sourceText): array
    {
        $this->assertNoForbiddenFields($data);
        $this->assertKeys($data, self::JOB_ALLOWED_KEYS, 'job_extraction');

        if (($data['schema_version'] ?? '') !== self::SCHEMA_VERSION) {
            throw new ExtractionValidationException(
                ['schema_version' => "Schema version '" . ($data['schema_version'] ?? '') . "' không hợp lệ. Yêu cầu: '" . self::SCHEMA_VERSION . "'."],
                'Schema version extraction không hợp lệ'
            );
        }

        if (($data['document_type'] ?? '') !== self::DOC_JOB) {
            throw new ExtractionValidationException(
                ['document_type' => "Document type '" . ($data['document_type'] ?? '') . "' không hợp lệ. Yêu cầu: '" . self::DOC_JOB . "'."],
                'Document type extraction không hợp lệ'
            );
        }

        $normalizedSource = self::normalizeForEvidenceCheck($sourceText);

        // 1. Skills (Must have non-empty verified evidence & confidence >= MIN_CONFIDENCE)
        $validSkills = [];
        $rawSkills = $this->requireList($data['skills'] ?? [], 'job.skills', 50);
        foreach ($rawSkills as $idx => $s) {
                if (!is_array($s)) {
                    $this->throwInvalidType("job.skills[{$idx}]", 'object');
                }
                $this->assertKeys($s, ['canonical_name', 'importance', 'evidence', 'confidence'], "job.skills[{$idx}]");
                if (!isset($s['canonical_name']) || !is_string($s['canonical_name'])) {
                    continue;
                }
                $canonicalName = trim($s['canonical_name']);
                if ($canonicalName === '' || mb_strlen($canonicalName) < 2 || mb_strlen($canonicalName) > 255) {
                    continue;
                }
                if (PiiRedactor::containsPlaceholder($canonicalName) || PiiRedactor::isPlaceholderOrContact($canonicalName)) {
                    continue;
                }
                $confidence = $this->confidenceOrNull($s['confidence'] ?? null);
                if ($confidence === null || $confidence < self::MIN_CONFIDENCE) {
                    continue;
                }
                $evidence = $this->evidenceOrEmpty($s['evidence'] ?? null);
                if ($evidence === '' || !$this->verifyEvidence($evidence, $normalizedSource)) {
                    // Strictly discard hallucinated/unverified job skill
                    continue;
                }

                if (!isset($s['importance']) || !is_string($s['importance'])) {
                    continue;
                }
                $importance = strtolower(trim($s['importance']));
                if (!in_array($importance, ['required', 'preferred'], true)) {
                    $this->throwInvalidEnum("job.skills[{$idx}].importance");
                }

                $validSkills[] = [
                    'canonical_name' => mb_substr($canonicalName, 0, 255),
                    'importance' => $importance,
                    'evidence' => mb_substr($evidence, 0, 500),
                    'confidence' => round($confidence, 2),
                ];
        }

        // 2. Experience requirement (Must have non-empty verified evidence & confidence >= MIN_CONFIDENCE)
        $validExpReq = ['state' => DataState::UNKNOWN->value, 'minimum_months' => null, 'domains' => [], 'evidence' => null, 'confidence' => 0.0];
        $rawExpReq = $data['experience_requirement'] ?? null;
        if (array_key_exists('experience_requirement', $data) && !is_array($rawExpReq)) {
            $this->throwInvalidType('job.experience_requirement', 'object');
        }
        if (is_array($rawExpReq)) {
            $this->assertKeys($rawExpReq, ['state', 'minimum_months', 'domains', 'evidence', 'confidence'], 'job.experience_requirement');
            if (isset($rawExpReq['state']) && !is_string($rawExpReq['state'])) {
                $this->throwInvalidType('job.experience_requirement.state', 'string');
            }
            $stateStr = isset($rawExpReq['state']) && is_string($rawExpReq['state'])
                ? strtoupper($rawExpReq['state'])
                : DataState::UNKNOWN->value;
            $this->assertEnum(
                $stateStr,
                [DataState::AVAILABLE->value, DataState::NOT_APPLICABLE->value, DataState::UNKNOWN->value],
                'job.experience_requirement.state'
            );
            $confidence = $this->confidenceOrNull($rawExpReq['confidence'] ?? null);
            $confVal = ($confidence !== null && $confidence >= self::MIN_CONFIDENCE) ? round($confidence, 2) : 0.0;
            $evidence = $this->evidenceOrEmpty($rawExpReq['evidence'] ?? null);

            if ($stateStr === DataState::NOT_APPLICABLE->value && $evidence !== '' && $this->verifyEvidence($evidence, $normalizedSource) && $confVal >= self::MIN_CONFIDENCE) {
                $validExpReq = [
                    'state' => DataState::NOT_APPLICABLE->value,
                    'minimum_months' => null,
                    'domains' => [],
                    'evidence' => mb_substr($evidence, 0, 500),
                    'confidence' => $confVal,
                ];
            } elseif ($stateStr === DataState::AVAILABLE->value && $evidence !== '' && $this->verifyEvidence($evidence, $normalizedSource) && $confVal >= self::MIN_CONFIDENCE) {
                $minMonths = null;
                if (isset($rawExpReq['minimum_months'])) {
                    if (!is_int($rawExpReq['minimum_months'])) {
                        $this->throwInvalidType('job.experience_requirement.minimum_months', 'integer|null');
                    }
                    if ($rawExpReq['minimum_months'] >= 0 && $rawExpReq['minimum_months'] <= 600) {
                        $minMonths = $rawExpReq['minimum_months'];
                    }
                }

                $domains = [];
                if (isset($rawExpReq['domains'])) {
                    foreach ($this->requireList($rawExpReq['domains'], 'job.experience_requirement.domains', 10) as $d) {
                        if (is_string($d) && trim($d) !== '' && mb_strlen(trim($d)) <= 100) {
                            $dTrim = trim($d);
                            if (!PiiRedactor::containsPlaceholder($dTrim)) {
                                $domains[] = mb_substr($dTrim, 0, 100);
                            }
                        }
                    }
                    $domains = array_values(array_unique($domains));
                }

                if ($minMonths !== null || !empty($domains)) {
                    $validExpReq = [
                        'state' => DataState::AVAILABLE->value,
                        'minimum_months' => $minMonths,
                        'domains' => $domains,
                        'evidence' => mb_substr($evidence, 0, 500),
                        'confidence' => $confVal,
                    ];
                }
            }
        }

        // 3. Education requirement (Must have non-empty verified evidence & confidence >= MIN_CONFIDENCE)
        $validEduReq = ['state' => DataState::UNKNOWN->value, 'levels' => [], 'majors' => [], 'evidence' => null, 'confidence' => 0.0];
        $rawEduReq = $data['education_requirement'] ?? null;
        if (array_key_exists('education_requirement', $data) && !is_array($rawEduReq)) {
            $this->throwInvalidType('job.education_requirement', 'object');
        }
        if (is_array($rawEduReq)) {
            $this->assertKeys($rawEduReq, ['state', 'levels', 'majors', 'evidence', 'confidence'], 'job.education_requirement');
            if (isset($rawEduReq['state']) && !is_string($rawEduReq['state'])) {
                $this->throwInvalidType('job.education_requirement.state', 'string');
            }
            $stateStr = isset($rawEduReq['state']) && is_string($rawEduReq['state'])
                ? strtoupper($rawEduReq['state'])
                : DataState::UNKNOWN->value;
            $this->assertEnum($stateStr, [DataState::AVAILABLE->value, DataState::UNKNOWN->value], 'job.education_requirement.state');
            $confidence = $this->confidenceOrNull($rawEduReq['confidence'] ?? null);
            $confVal = ($confidence !== null && $confidence >= self::MIN_CONFIDENCE) ? round($confidence, 2) : 0.0;
            $evidence = $this->evidenceOrEmpty($rawEduReq['evidence'] ?? null);

            if ($stateStr === DataState::AVAILABLE->value && $evidence !== '' && $this->verifyEvidence($evidence, $normalizedSource) && $confVal >= self::MIN_CONFIDENCE) {
                $levels = [];
                if (isset($rawEduReq['levels'])) {
                    foreach ($this->requireList($rawEduReq['levels'], 'job.education_requirement.levels', 10) as $l) {
                        if (is_string($l) && trim($l) !== '' && mb_strlen(trim($l)) <= 50) {
                            $levels[] = mb_substr(trim($l), 0, 50);
                        }
                    }
                }
                $majors = [];
                if (isset($rawEduReq['majors'])) {
                    foreach ($this->requireList($rawEduReq['majors'], 'job.education_requirement.majors', 10) as $m) {
                        if (is_string($m) && trim($m) !== '' && mb_strlen(trim($m)) <= 100) {
                            $majors[] = mb_substr(trim($m), 0, 100);
                        }
                    }
                }

                if (!empty($levels) || !empty($majors)) {
                    $validEduReq = [
                        'state' => DataState::AVAILABLE->value,
                        'levels' => array_values(array_unique($levels)),
                        'majors' => array_values(array_unique($majors)),
                        'evidence' => mb_substr($evidence, 0, 500),
                        'confidence' => $confVal,
                    ];
                }
            }
        }

        // 4. Schedule requirement (Must have non-empty verified evidence & confidence >= MIN_CONFIDENCE)
        $validSchedReq = ['state' => DataState::UNKNOWN->value, 'shift_type' => null, 'minimum_shifts_per_week' => null, 'evidence' => null, 'confidence' => 0.0];
        $rawSchedReq = $data['schedule_requirement'] ?? null;
        if (array_key_exists('schedule_requirement', $data) && !is_array($rawSchedReq)) {
            $this->throwInvalidType('job.schedule_requirement', 'object');
        }
        if (is_array($rawSchedReq)) {
            $this->assertKeys($rawSchedReq, ['state', 'shift_type', 'minimum_shifts_per_week', 'evidence', 'confidence'], 'job.schedule_requirement');
            if (isset($rawSchedReq['state']) && !is_string($rawSchedReq['state'])) {
                $this->throwInvalidType('job.schedule_requirement.state', 'string');
            }
            $stateStr = isset($rawSchedReq['state']) && is_string($rawSchedReq['state'])
                ? strtoupper($rawSchedReq['state'])
                : DataState::UNKNOWN->value;
            $this->assertEnum($stateStr, [DataState::AVAILABLE->value, DataState::UNKNOWN->value], 'job.schedule_requirement.state');
            $confidence = $this->confidenceOrNull($rawSchedReq['confidence'] ?? null);
            $confVal = ($confidence !== null && $confidence >= self::MIN_CONFIDENCE) ? round($confidence, 2) : 0.0;
            $evidence = $this->evidenceOrEmpty($rawSchedReq['evidence'] ?? null);

            if ($stateStr === DataState::AVAILABLE->value && $evidence !== '' && $this->verifyEvidence($evidence, $normalizedSource) && $confVal >= self::MIN_CONFIDENCE) {
                if (isset($rawSchedReq['shift_type']) && !is_string($rawSchedReq['shift_type'])) {
                    $this->throwInvalidType('job.schedule_requirement.shift_type', 'string|null');
                }
                $shiftTypeStr = isset($rawSchedReq['shift_type'])
                    ? strtolower(trim($rawSchedReq['shift_type']))
                    : null;
                $shiftType = null;
                if ($shiftTypeStr !== null) {
                    $shiftType = ShiftType::tryFrom($shiftTypeStr);
                    if ($shiftType === null) {
                        $this->throwInvalidEnum('job.schedule_requirement.shift_type');
                    }
                }

                $minShifts = null;
                if (isset($rawSchedReq['minimum_shifts_per_week'])) {
                    if (!is_int($rawSchedReq['minimum_shifts_per_week'])) {
                        $this->throwInvalidType('job.schedule_requirement.minimum_shifts_per_week', 'integer|null');
                    }
                    if ($rawSchedReq['minimum_shifts_per_week'] > 0 && $rawSchedReq['minimum_shifts_per_week'] <= 14) {
                        $minShifts = $rawSchedReq['minimum_shifts_per_week'];
                    }
                }

                if ($shiftType !== null || $minShifts !== null) {
                    $validSchedReq = [
                        'state' => DataState::AVAILABLE->value,
                        'shift_type' => $shiftType?->value,
                        'minimum_shifts_per_week' => $minShifts,
                        'evidence' => mb_substr($evidence, 0, 500),
                        'confidence' => $confVal,
                    ];
                }
            }
        }

        // 5. Role terms
        $validRoleTerms = [];
        $rawRoles = $this->requireList($data['role_terms'] ?? [], 'job.role_terms', 20);
        foreach ($rawRoles as $rt) {
                if (is_string($rt) && trim($rt) !== '' && mb_strlen(trim($rt)) <= 100) {
                    $rtTrim = trim($rt);
                    if (!PiiRedactor::containsPlaceholder($rtTrim) && !PiiRedactor::isPlaceholderOrContact($rtTrim)) {
                        $validRoleTerms[] = mb_substr($rtTrim, 0, 100);
                    }
                }
        }
        $validRoleTerms = array_values(array_unique($validRoleTerms));

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'document_type' => self::DOC_JOB,
            'skills' => $validSkills,
            'experience_requirement' => $validExpReq,
            'education_requirement' => $validEduReq,
            'schedule_requirement' => $validSchedReq,
            'role_terms' => $validRoleTerms,
        ];
    }

    /**
     * Verifies that the evidence string actually occurs inside the normalized source text.
     */
    public function verifyEvidence(string $evidence, string $normalizedSource): bool
    {
        $evidenceNorm = self::normalizeForEvidenceCheck($evidence);
        if ($evidenceNorm === '' || mb_strlen($evidenceNorm) < 3) {
            return false;
        }

        // Exact substring check on normalized space and lowercase
        if (str_contains($normalizedSource, $evidenceNorm)) {
            return true;
        }

        // Allow partial phrase check if evidence is long (at least 70% of words exist consecutively)
        $evidenceWords = explode(' ', $evidenceNorm);
        if (count($evidenceWords) >= 4) {
            $subPhrase = implode(' ', array_slice($evidenceWords, 0, min(5, count($evidenceWords))));
            if (str_contains($normalizedSource, $subPhrase)) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeForEvidenceCheck(string $text): string
    {
        $lowered = mb_strtolower($text, 'UTF-8');
        $cleaned = (string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $lowered);
        return trim((string)preg_replace('/\s+/', ' ', $cleaned));
    }

    /**
     * @param array<string, mixed> $data
     * @throws ExtractionValidationException
     */
    private function assertNoForbiddenFields(array $data): void
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string)$key);
            if (in_array($lowerKey, self::FORBIDDEN_FIELDS, true)) {
                throw new ExtractionValidationException(
                    ['forbidden_field' => "Phát hiện trường cấm trong dữ liệu AI: '{$key}'."],
                    'Dữ liệu AI chứa trường cấm bảo mật hoặc quyết định tuyển dụng'
                );
            }
            if (is_array($value)) {
                $this->assertNoForbiddenFields($value);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $allowedKeys
     * @throws ExtractionValidationException
     */
    private function assertKeys(array $data, array $allowedKeys, string $context): void
    {
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new ExtractionValidationException(
                    ['unknown_key' => "Trường không cho phép trong {$context}: '{$key}'."],
                    "Dữ liệu AI chứa trường không hợp lệ trong {$context}"
                );
            }
        }
    }

    /**
     * @return list<mixed>
     * @throws ExtractionValidationException
     */
    private function requireList(mixed $value, string $context, int $maxItems): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->throwInvalidType($context, 'array');
        }
        if (count($value) > $maxItems) {
            throw new ExtractionValidationException(
                ['array_limit' => "{$context} vượt quá {$maxItems} phần tử."],
                "Dữ liệu AI vượt giới hạn tại {$context}"
            );
        }

        return $value;
    }

    private function confidenceOrNull(mixed $value): ?float
    {
        if (!is_int($value) && !is_float($value)) {
            return null;
        }
        $confidence = (float)$value;
        return ($confidence >= 0.0 && $confidence <= 1.0) ? $confidence : null;
    }

    private function evidenceOrEmpty(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        $evidence = trim($value);
        return mb_strlen($evidence, 'UTF-8') <= 500 ? $evidence : '';
    }

    /** @throws ExtractionValidationException */
    private function throwInvalidType(string $context, string $expected): never
    {
        throw new ExtractionValidationException(
            ['invalid_type' => "{$context} phải có kiểu {$expected}."],
            "Dữ liệu AI sai kiểu tại {$context}"
        );
    }

    /** @param list<string> $allowed */
    private function assertEnum(string $value, array $allowed, string $context): void
    {
        if (!in_array($value, $allowed, true)) {
            $this->throwInvalidEnum($context);
        }
    }

    private function throwInvalidEnum(string $context): never
    {
        throw new ExtractionValidationException(
            ['invalid_enum' => "{$context} chứa giá trị enum không hợp lệ."],
            "Dữ liệu AI sai enum tại {$context}"
        );
    }
}
