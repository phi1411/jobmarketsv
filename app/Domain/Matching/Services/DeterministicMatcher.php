<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services;

use JobMarket\Domain\Matching\Contracts\CandidateProfileContract;
use JobMarket\Domain\Matching\Contracts\JobRequirementsContract;
use JobMarket\Domain\Matching\Contracts\MatchResultContract;
use JobMarket\Domain\Matching\Enums\CriterionName;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\MatchClassification;
use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\ValueObjects\CriterionEvaluation;

final class DeterministicMatcher
{
    public const MATCHER_VERSION = 'matcher.v2.0-five-criteria';

    public const WEIGHT_AGE = 10.0;
    public const WEIGHT_SKILLS = 30.0;
    public const WEIGHT_AVAILABILITY = 30.0;
    public const WEIGHT_EXPERIENCE = 15.0;
    public const WEIGHT_EDUCATION = 15.0;
    // Giữ hằng số 0 để dữ liệu/phép thử cũ còn đọc được; matcher v2 không phát sinh các tiêu chí này.
    public const WEIGHT_LOCATION = 0.0;
    public const WEIGHT_ROLE_RELEVANCE = 0.0;
    public const WEIGHT_SALARY = 0.0;

    public const EXP_WEIGHT_DURATION = 0.5;
    public const EXP_WEIGHT_DOMAIN = 0.5;

    /**
     * Server-owned synonym allowlist for deterministic skill matching.
     * Maps match_key to canonical equivalence group.
     *
     * @var array<string, list<string>>
     */
    private const SYNONYM_MAP = [
        'thu ngan' => ['thu ngan & pos', 'thu ngan', 'pos', 'thu ngan sieu thi'],
        'thu ngan & pos' => ['thu ngan & pos', 'thu ngan', 'pos'],
        'phuc vu' => ['phuc vu', 'chay ban', 'tiep thuc', 'order'],
        'chay ban' => ['phuc vu', 'chay ban'],
        'ban hang' => ['ban hang', 'tu van ban hang', 'sales', 'tu van khach hang'],
        'tu van ban hang' => ['ban hang', 'tu van ban hang', 'sales'],
        'giao tiep' => ['giao tiep', 'giao tiep khach hang', 'cham soc khach hang'],
        'giao tiep khach hang' => ['giao tiep', 'giao tiep khach hang', 'cham soc khach hang'],
        'pha che' => ['pha che', 'barista', 'bartender'],
        'barista' => ['pha che', 'barista'],
    ];

    /**
     * Executes the deterministic match analysis between a candidate profile and job requirements.
     */
    public function match(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job
    ): MatchResultContract {
        $criteria = [];
        $strengths = [];
        $considerations = [];
        $missingData = [];

        // Chỉ chấm đúng 5 tiêu chí đã công bố cho cả sinh viên và nhà tuyển dụng.
        $criteria[] = $this->evaluateAge($candidate, $job, $strengths, $considerations, $missingData);

        // 1. Skills Evaluation (Weight 30)
        $skillsEval = $this->evaluateSkills($candidate, $job, $strengths, $considerations, $missingData);
        $criteria[] = $skillsEval;

        // 2. Availability & Shift Evaluation (Weight 30)
        $availabilityEval = $this->evaluateAvailability($candidate, $job, $strengths, $considerations, $missingData);
        $criteria[] = $availabilityEval;

        // 3. Experience Evaluation (Weight 15)
        $experienceEval = $this->evaluateExperience($candidate, $job, $strengths, $considerations, $missingData);
        $criteria[] = $experienceEval;

        // 4. Education Evaluation (Weight 15)
        $educationEval = $this->evaluateEducation($candidate, $job, $strengths, $considerations, $missingData);
        $criteria[] = $educationEval;

        // Calculate Overall Score and Coverage
        $effectiveWeightSum = 0.0;
        $weightedScoreSum = 0.0;

        foreach ($criteria as $eval) {
            if ($eval->state === DataState::AVAILABLE && $eval->score !== null) {
                $effectiveWeightSum += $eval->weight;
                $weightedScoreSum += ($eval->weight * $eval->score);
            }
        }

        $coveragePercent = round(($effectiveWeightSum / 100.0) * 100.0, 1);

        $overallScore = null;
        if ($effectiveWeightSum > 0.0) {
            $overallScore = round($weightedScoreSum / $effectiveWeightSum, 1);
        }

        // Determine Classification
        if ($coveragePercent < 60.0) {
            $classification = MatchClassification::INSUFFICIENT_DATA;
        } elseif ($overallScore !== null && $overallScore >= 80.0) {
            $classification = MatchClassification::HIGH_MATCH;
        } elseif ($overallScore !== null && $overallScore >= 65.0) {
            $classification = MatchClassification::GOOD_MATCH;
        } else {
            $classification = MatchClassification::REVIEW_NEEDED;
        }

        return new MatchResultContract(
            schemaVersion: MatchResultContract::SCHEMA_VERSION,
            candidateId: $candidate->sourceApplicationId ?? $candidate->sourceProfileId,
            jobId: $job->sourceJobId,
            overallScore: $overallScore,
            coveragePercent: $coveragePercent,
            classification: $classification,
            criteria: $criteria,
            strengths: array_values(array_unique($strengths)),
            considerations: array_values(array_unique($considerations)),
            missingData: $missingData,
            disclaimer: MatchResultContract::DEFAULT_DISCLAIMER
        );
    }

    private function evaluateAge(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job,
        array &$strengths,
        array &$considerations,
        array &$missingData
    ): CriterionEvaluation {
        $minimumAge = $job->minimumAge;
        $maximumAge = $job->maximumAge;

        if ($minimumAge === null && $maximumAge === null) {
            return new CriterionEvaluation(
                criterionName: CriterionName::AGE,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_AGE,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Nhà tuyển dụng không yêu cầu độ tuổi',
                details: ['requirement_applied' => false]
            );
        }

        if ($candidate->ageYears === null) {
            $missingData['age_years'] = DataState::UNKNOWN->value;
            $considerations[] = 'Hồ sơ chưa có ngày sinh để đối chiếu yêu cầu độ tuổi.';
            return new CriterionEvaluation(
                criterionName: CriterionName::AGE,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_AGE,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Hồ sơ chưa có dữ liệu tuổi',
                details: ['requirement_applied' => true, 'minimum_age' => $minimumAge, 'maximum_age' => $maximumAge]
            );
        }

        $matched = ($minimumAge === null || $candidate->ageYears >= $minimumAge)
            && ($maximumAge === null || $candidate->ageYears <= $maximumAge);
        if ($matched) {
            $strengths[] = 'Độ tuổi đáp ứng yêu cầu của tin tuyển dụng.';
        } else {
            $considerations[] = 'Độ tuổi hiện tại chưa nằm trong khoảng nhà tuyển dụng yêu cầu.';
        }

        $range = $minimumAge !== null && $maximumAge !== null
            ? "{$minimumAge}–{$maximumAge} tuổi"
            : ($minimumAge !== null ? "từ {$minimumAge} tuổi" : "đến {$maximumAge} tuổi");

        return new CriterionEvaluation(
            criterionName: CriterionName::AGE,
            state: DataState::AVAILABLE,
            score: $matched ? 100.0 : 0.0,
            weight: self::WEIGHT_AGE,
            confidence: 1.0,
            provenance: Provenance::STRUCTURED,
            evidence: $matched ? "Đáp ứng yêu cầu độ tuổi ({$range})" : "Chưa đáp ứng yêu cầu độ tuổi ({$range})",
            details: ['requirement_applied' => true, 'minimum_age' => $minimumAge, 'maximum_age' => $maximumAge]
        );
    }

    private function evaluateSkills(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job,
        array &$strengths,
        array &$considerations,
        array &$missingData
    ): CriterionEvaluation {
        $jobSkills = $job->skills;

        if (empty($jobSkills)) {
            return new CriterionEvaluation(
                criterionName: CriterionName::SKILLS,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_SKILLS,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Tin tuyển dụng không yêu cầu kỹ năng cụ thể',
                details: ['requirement_applied' => false]
            );
        }

        $candidateSkills = $candidate->skills;
        if (empty($candidateSkills)) {
            $missingData['skills'] = DataState::UNKNOWN->value;
            $considerations[] = 'Hồ sơ chưa có thông tin kỹ năng để so sánh với yêu cầu công việc.';
            return new CriterionEvaluation(
                criterionName: CriterionName::SKILLS,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_SKILLS,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Hồ sơ ứng viên chưa điền kỹ năng',
                details: ['missing' => 'candidate_skills_empty']
            );
        }

        $totalRatio = 0.0;
        $matchedNames = [];
        $unmatchedNames = [];

        foreach ($jobSkills as $jSkill) {
            $jKey = $jSkill->matchKey ?? SkillNormalizer::generateMatchKey($jSkill->canonicalName);
            $jId = $jSkill->sourceSkillId;

            $bestRatio = 0.0;
            $matchedWith = null;

            foreach ($candidateSkills as $cSkill) {
                $cKey = $cSkill->matchKey ?? SkillNormalizer::generateMatchKey($cSkill->canonicalName);
                $cId = $cSkill->sourceSkillId;

                // 1. Exact ID or matchKey match = 100%
                if (($jId !== null && $cId !== null && $jId === $cId) || ($jKey === $cKey)) {
                    $bestRatio = 1.0;
                    $matchedWith = $cSkill->canonicalName;
                    break;
                }

                // 2. Approved Synonym match = 80%
                if ($this->isSynonym($jKey, $cKey)) {
                    if ($bestRatio < 0.8) {
                        $bestRatio = 0.8;
                        $matchedWith = $cSkill->canonicalName;
                    }
                }

                // 3. Partial / transferable word overlap = 50%
                if ($this->hasSubstantialWordOverlap($jKey, $cKey)) {
                    if ($bestRatio < 0.5) {
                        $bestRatio = 0.5;
                        $matchedWith = $cSkill->canonicalName;
                    }
                }
            }

            $totalRatio += $bestRatio;
            if ($bestRatio > 0.0) {
                $matchedNames[] = $jSkill->canonicalName . ($matchedWith ? " (khớp với {$matchedWith})" : '');
            } else {
                $unmatchedNames[] = $jSkill->canonicalName;
            }
        }

        $score = round(($totalRatio / count($jobSkills)) * 100.0, 1);

        if ($score >= 70.0) {
            $strengths[] = 'Kỹ năng hồ sơ phù hợp tốt với các yêu cầu cốt lõi của công việc.';
        } elseif (!empty($unmatchedNames)) {
            $considerations[] = 'Còn thiếu một số kỹ năng tin tuyển dụng yêu cầu: ' . implode(', ', array_slice($unmatchedNames, 0, 3));
        }

        return new CriterionEvaluation(
            criterionName: CriterionName::SKILLS,
            state: DataState::AVAILABLE,
            score: $score,
            weight: self::WEIGHT_SKILLS,
            confidence: 1.0,
            provenance: Provenance::STRUCTURED,
            evidence: 'Khớp ' . count($matchedNames) . '/' . count($jobSkills) . ' kỹ năng yêu cầu',
            details: [
                'matched' => $matchedNames,
                'unmatched' => $unmatchedNames,
            ]
        );
    }

    private function evaluateAvailability(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job,
        array &$strengths,
        array &$considerations,
        array &$missingData
    ): CriterionEvaluation {
        $jobSched = $job->schedule;

        // If job schedule is UNKNOWN -> do not penalize candidate
        if (!$jobSched || $jobSched->state === DataState::UNKNOWN) {
            return new CriterionEvaluation(
                criterionName: CriterionName::AVAILABILITY,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_AVAILABILITY,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Nhà tuyển dụng không yêu cầu lịch làm việc cụ thể',
                details: ['requirement_applied' => false]
            );
        }

        if ($jobSched->state === DataState::NOT_APPLICABLE || $jobSched->shiftType === null) {
            return new CriterionEvaluation(
                criterionName: CriterionName::AVAILABILITY,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_AVAILABILITY,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Công việc không ràng buộc ca cụ thể',
                details: ['requirement_applied' => false]
            );
        }

        $candAvail = $candidate->availability;
        if (!$candAvail || $candAvail->state !== DataState::AVAILABLE) {
            $missingData['availability'] = DataState::UNKNOWN->value;
            $considerations[] = 'Hồ sơ chưa cập nhật lịch rảnh hàng tuần.';
            return new CriterionEvaluation(
                criterionName: CriterionName::AVAILABILITY,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_AVAILABILITY,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Chưa có dữ liệu lịch rảnh của ứng viên',
                details: []
            );
        }

        $jobShift = $jobSched->shiftType;
        $prefShift = $candAvail->preferredShiftForApplication;
        $slots = $candAvail->slots;

        $hasWeeklySchedule = !empty($slots);
        $matchingSlotCount = 0;
        foreach ($slots as $slot) {
            if ($slot->shift === $jobShift || $slot->shift === ShiftType::FLEXIBLE || $jobShift === ShiftType::FLEXIBLE) {
                $matchingSlotCount++;
            }
        }
        $scheduleHasCompatibleSlot = ($matchingSlotCount > 0);

        if ($hasWeeklySchedule) {
            if ($scheduleHasCompatibleSlot) {
                if ($prefShift !== null && ($prefShift === $jobShift || $prefShift === ShiftType::FLEXIBLE || $jobShift === ShiftType::FLEXIBLE)) {
                    $score = 100.0;
                    $evidence = "Lịch rảnh hàng tuần có {$matchingSlotCount} buổi phù hợp và ca mong muốn khi nộp đơn khớp ca '{$jobShift->value}'";
                    $strengths[] = "Lịch rảnh hàng tuần và ca làm việc bạn chọn khi ứng tuyển đều khớp với yêu cầu của công việc.";
                } else {
                    $score = ($matchingSlotCount >= 3) ? 90.0 : 75.0;
                    $evidence = "Lịch rảnh hàng tuần có {$matchingSlotCount} buổi phù hợp ca '{$jobShift->value}'";
                    $strengths[] = "Lịch rảnh hàng tuần có buổi phù hợp với ca làm việc của công việc.";
                }
            } else {
                // Conflict: weekly schedule has slots but none match job shift
                if ($prefShift !== null && ($prefShift === $jobShift || $prefShift === ShiftType::FLEXIBLE)) {
                    $score = 40.0;
                    $evidence = "Ca mong muốn khi nộp đơn khớp ca '{$jobShift->value}', nhưng lịch rảnh hàng tuần đã lưu chưa có buổi nào trùng ca này";
                    $considerations[] = "Lịch rảnh hàng tuần đã lưu chưa có buổi nào khớp ca '{$jobShift->value}' của công việc. Bạn nên trao đổi thêm với nhà tuyển dụng để sắp xếp ca.";
                } else {
                    $score = 15.0;
                    $evidence = "Cả lịch rảnh hàng tuần và ca mong muốn đều không khớp ca '{$jobShift->value}'";
                    $considerations[] = "Lịch rảnh đã lưu chưa khớp ca '{$jobShift->value}' của việc làm. Bạn nên trao đổi thêm với nhà tuyển dụng về khả năng sắp xếp ca.";
                }
            }
        } else {
            // Weekly schedule is missing, only application preferred shift is available
            if ($prefShift !== null && ($prefShift === $jobShift || $jobShift === ShiftType::FLEXIBLE)) {
                $score = 70.0;
                $evidence = "Ca đăng ký khi nộp đơn ({$prefShift->value}) khớp ca yêu cầu (chưa có dữ liệu lịch tuần chi tiết)";
                $strengths[] = "Ca làm việc bạn chọn khi ứng tuyển phù hợp với ca yêu cầu của công việc.";
            } elseif ($prefShift === ShiftType::FLEXIBLE) {
                $score = 65.0;
                $evidence = "Ứng viên đăng ký ca linh hoạt khi nộp đơn (chưa có dữ liệu lịch tuần chi tiết)";
            } else {
                $score = 20.0;
                $evidence = "Ca đăng ký khi nộp đơn không khớp ca '{$jobShift->value}'";
                $considerations[] = "Ca làm việc bạn chọn khi nộp đơn khác với ca yêu cầu của công việc.";
            }
        }

        return new CriterionEvaluation(
            criterionName: CriterionName::AVAILABILITY,
            state: DataState::AVAILABLE,
            score: $score,
            weight: self::WEIGHT_AVAILABILITY,
            confidence: $hasWeeklySchedule ? 1.0 : 0.75,
            provenance: Provenance::STRUCTURED,
            evidence: $evidence,
            details: [
                'job_shift' => $jobShift->value,
                'preferred_shift' => $prefShift?->value,
                'matching_slots_count' => $matchingSlotCount,
                'has_weekly_schedule' => $hasWeeklySchedule,
            ]
        );
    }

    private function evaluateExperience(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job,
        array &$strengths,
        array &$considerations,
        array &$missingData
    ): CriterionEvaluation {
        $jobExp = $job->experienceRequirement;

        // 1. If job explicitly specifies no experience required -> NOT_APPLICABLE
        if ($jobExp && $jobExp->state === DataState::NOT_APPLICABLE) {
            $strengths[] = 'Công việc không yêu cầu kinh nghiệm trước đó, rất phù hợp với sinh viên.';
            return new CriterionEvaluation(
                criterionName: CriterionName::EXPERIENCE,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_EXPERIENCE,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Tin tuyển dụng không yêu cầu kinh nghiệm',
                details: ['requirement_applied' => false]
            );
        }

        // 2. If job experience requirement is UNKNOWN -> criterion must be UNKNOWN, score null
        if (!$jobExp || $jobExp->state === DataState::UNKNOWN) {
            return new CriterionEvaluation(
                criterionName: CriterionName::EXPERIENCE,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_EXPERIENCE,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Nhà tuyển dụng không yêu cầu kinh nghiệm',
                details: ['requirement_applied' => false]
            );
        }

        // 3. Job requires experience (AVAILABLE)
        $candExp = $candidate->experience;
        if (!$candExp || $candExp->state !== DataState::AVAILABLE || empty($candExp->items)) {
            $missingData['experience'] = DataState::UNKNOWN->value;
            return new CriterionEvaluation(
                criterionName: CriterionName::EXPERIENCE,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_EXPERIENCE,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Hồ sơ chưa có thông tin kinh nghiệm',
                details: []
            );
        }

        // Check if job requirement AVAILABLE has usable criteria
        $hasJobMonths = ($jobExp->minimumMonths !== null);
        $hasJobDomains = (!empty($jobExp->domains));

        if (!$hasJobMonths && !$hasJobDomains && empty($jobExp->evidence)) {
            $missingData['job_experience_criteria'] = DataState::UNKNOWN->value;
            return new CriterionEvaluation(
                criterionName: CriterionName::EXPERIENCE,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_EXPERIENCE,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Yêu cầu kinh nghiệm chưa có tiêu chí số tháng hoặc lĩnh vực cụ thể',
                details: ['reason' => 'no_minimum_months_or_domains']
            );
        }

        // Calculate candidate duration
        $candidateTotalMonths = 0;
        $hasCandidateDuration = false;
        foreach ($candExp->items as $item) {
            if ($item->durationMonths !== null) {
                $candidateTotalMonths += $item->durationMonths;
                $hasCandidateDuration = true;
            }
        }

        // 1. Duration scoring (capped ratio: min(100, candidate / minimum * 100))
        $durationScore = null;
        if ($hasJobMonths) {
            if ($jobExp->minimumMonths <= 0) {
                $durationScore = 100.0;
            } elseif ($hasCandidateDuration) {
                $ratio = $candidateTotalMonths / $jobExp->minimumMonths;
                $durationScore = min(100.0, max(0.0, round($ratio * 100.0, 1)));
            } else {
                $durationScore = null;
            }
        }

        // 2. Domain matching
        $domainScore = null;
        $matchedDomains = [];
        if ($hasJobDomains) {
            $domainRatios = [];
            foreach ($jobExp->domains as $domain) {
                $dKey = SkillNormalizer::generateMatchKey($domain);
                $bestRatio = 0.0;
                foreach ($candExp->items as $item) {
                    $rKey = SkillNormalizer::generateMatchKey($item->role);
                    if ($dKey === $rKey || str_contains($rKey, $dKey) || str_contains($dKey, $rKey)) {
                        $bestRatio = max($bestRatio, 1.0);
                    } elseif ($this->isSynonym($dKey, $rKey)) {
                        $bestRatio = max($bestRatio, 0.8);
                    } elseif ($this->hasSubstantialWordOverlap($dKey, $rKey, true)) {
                        $bestRatio = max($bestRatio, 0.5);
                    }
                }
                $domainRatios[] = $bestRatio;
                if ($bestRatio > 0.0) {
                    $matchedDomains[] = $domain;
                }
            }
            $domainScore = round((array_sum($domainRatios) / count($domainRatios)) * 100.0, 1);
        }

        // 3. Combined scoring based on available signals
        if ($hasJobMonths && $hasJobDomains) {
            if ($durationScore !== null) {
                $score = round($durationScore * self::EXP_WEIGHT_DURATION + $domainScore * self::EXP_WEIGHT_DOMAIN, 1);
                $evidence = "Kinh nghiệm: {$candidateTotalMonths}/{$jobExp->minimumMonths} tháng (ratio {$durationScore}%), khớp " . count($matchedDomains) . "/" . count($jobExp->domains) . " lĩnh vực ({$domainScore}%)";
            } else {
                // Domain known, duration unknown: cautious duration credit 50%
                $score = round($domainScore * self::EXP_WEIGHT_DOMAIN + 50.0 * self::EXP_WEIGHT_DURATION, 1);
                $evidence = "Khớp " . count($matchedDomains) . "/" . count($jobExp->domains) . " lĩnh vực ({$domainScore}%), chưa rõ số tháng kinh nghiệm";
            }
        } elseif ($hasJobMonths) {
            if ($durationScore !== null) {
                $score = $durationScore;
                $evidence = "Kinh nghiệm: {$candidateTotalMonths}/{$jobExp->minimumMonths} tháng yêu cầu ({$durationScore}%)";
            } else {
                $score = 50.0;
                $evidence = "Có " . count($candExp->items) . " mục kinh nghiệm, chưa xác định số tháng cụ thể đối chiếu yêu cầu {$jobExp->minimumMonths} tháng";
            }
        } elseif ($hasJobDomains) {
            $score = $domainScore;
            $evidence = "Khớp " . count($matchedDomains) . "/" . count($jobExp->domains) . " lĩnh vực kinh nghiệm ({$domainScore}%)";
        } else {
            $score = 70.0;
            $evidence = "Ứng viên có " . count($candExp->items) . " mục kinh nghiệm làm việc";
        }

        if ($score >= 70.0) {
            $strengths[] = 'Kinh nghiệm làm việc thực tế của bạn phù hợp tốt với yêu cầu công việc.';
        } elseif ($score < 40.0) {
            $considerations[] = 'Kinh nghiệm làm việc hiện có chưa đáp ứng đầy đủ yêu cầu về thời gian hoặc lĩnh vực của công việc.';
        }

        return new CriterionEvaluation(
            criterionName: CriterionName::EXPERIENCE,
            state: DataState::AVAILABLE,
            score: $score,
            weight: self::WEIGHT_EXPERIENCE,
            confidence: ($durationScore !== null || $domainScore !== null) ? 0.9 : 0.75,
            provenance: Provenance::STRUCTURED,
            evidence: $evidence,
            details: [
                'duration_score' => $durationScore,
                'domain_score' => $domainScore,
                'candidate_total_months' => $candidateTotalMonths,
                'job_minimum_months' => $jobExp->minimumMonths,
                'matched_domains' => $matchedDomains,
            ]
        );
    }

    private function evaluateEducation(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job,
        array &$strengths,
        array &$considerations,
        array &$missingData
    ): CriterionEvaluation {
        $jobEdu = $job->educationRequirement;

        if (!$jobEdu || $jobEdu->state === DataState::UNKNOWN || empty($jobEdu->majors)) {
            return new CriterionEvaluation(
                criterionName: CriterionName::EDUCATION,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_EDUCATION,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Công việc không yêu cầu chuyên ngành học cụ thể',
                details: ['requirement_applied' => false]
            );
        }

        $candEdu = $candidate->education;
        if (!$candEdu || $candEdu->state !== DataState::AVAILABLE || empty($candEdu->major)) {
            $missingData['education'] = DataState::UNKNOWN->value;
            return new CriterionEvaluation(
                criterionName: CriterionName::EDUCATION,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_EDUCATION,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Hồ sơ chưa có thông tin ngành học',
                details: []
            );
        }

        $candMajorKey = SkillNormalizer::generateMatchKey($candEdu->major);
        $matched = false;
        foreach ($jobEdu->majors as $reqMajor) {
            if (SkillNormalizer::generateMatchKey($reqMajor) === $candMajorKey) {
                $matched = true;
                break;
            }
        }

        $score = $matched ? 100.0 : 40.0;
        if ($matched) {
            $strengths[] = "Ngành học '{$candEdu->major}' khớp đúng với định hướng công việc.";
        }

        return new CriterionEvaluation(
            criterionName: CriterionName::EDUCATION,
            state: DataState::AVAILABLE,
            score: $score,
            weight: self::WEIGHT_EDUCATION,
            confidence: 0.95,
            provenance: Provenance::STRUCTURED,
            evidence: $matched ? "Ngành học '{$candEdu->major}' khớp yêu cầu" : "Ngành học khác ngành yêu cầu",
            details: []
        );
    }

    private function evaluateLocation(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job,
        array &$strengths,
        array &$considerations,
        array &$missingData
    ): CriterionEvaluation {
        $jobLoc = $job->location;

        if ($jobLoc && $jobLoc->workMode === 'remote') {
            $strengths[] = 'Công việc làm việc từ xa (remote), không giới hạn khoảng cách địa lý.';
            return new CriterionEvaluation(
                criterionName: CriterionName::LOCATION,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_LOCATION,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Hình thức làm việc từ xa (remote)',
                details: ['work_mode' => 'remote']
            );
        }

        // If job location is UNKNOWN -> do not penalize candidate
        if (!$jobLoc || $jobLoc->state === DataState::UNKNOWN) {
            return new CriterionEvaluation(
                criterionName: CriterionName::LOCATION,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_LOCATION,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Tin tuyển dụng chưa xác định địa điểm làm việc',
                details: ['reason' => 'job_location_unknown']
            );
        }

        if ($jobLoc->state === DataState::NOT_APPLICABLE) {
            return new CriterionEvaluation(
                criterionName: CriterionName::LOCATION,
                state: DataState::NOT_APPLICABLE,
                score: null,
                weight: self::WEIGHT_LOCATION,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Công việc không chỉ định địa điểm cụ thể',
                details: []
            );
        }

        $candLoc = $candidate->locations;
        if (!$candLoc || $candLoc->state !== DataState::AVAILABLE || (empty($candLoc->locationIds) && empty($candLoc->names))) {
            $missingData['location'] = DataState::UNKNOWN->value;
            return new CriterionEvaluation(
                criterionName: CriterionName::LOCATION,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_LOCATION,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Hồ sơ chưa có thông tin khu vực làm việc mong muốn',
                details: []
            );
        }

        // 1. Exact location ID match
        if ($jobLoc->locationId !== null && in_array($jobLoc->locationId, $candLoc->locationIds, true)) {
            $strengths[] = 'Khu vực làm việc nằm trong địa bàn ưu tiên của bạn.';
            return new CriterionEvaluation(
                criterionName: CriterionName::LOCATION,
                state: DataState::AVAILABLE,
                score: 100.0,
                weight: self::WEIGHT_LOCATION,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Khớp chính xác khu vực theo mã địa điểm',
                details: ['location_id' => $jobLoc->locationId]
            );
        }

        // 2. District & City match
        $matchedCity = false;
        $matchedDistrict = false;

        $jobCityKey = $jobLoc->city ? SkillNormalizer::generateMatchKey($jobLoc->city) : null;
        $jobDistKey = $jobLoc->district ? SkillNormalizer::generateMatchKey($jobLoc->district) : null;

        foreach ($candLoc->names as $name) {
            $nKey = SkillNormalizer::generateMatchKey($name);
            if ($jobDistKey !== null && str_contains($nKey, $jobDistKey)) {
                $matchedDistrict = true;
            }
            if ($jobCityKey !== null && str_contains($nKey, $jobCityKey)) {
                $matchedCity = true;
            }
        }

        if ($matchedDistrict) {
            $strengths[] = 'Địa điểm làm việc cùng quận/huyện với khu vực mong muốn của bạn.';
            return new CriterionEvaluation(
                criterionName: CriterionName::LOCATION,
                state: DataState::AVAILABLE,
                score: 80.0,
                weight: self::WEIGHT_LOCATION,
                confidence: 0.9,
                provenance: Provenance::STRUCTURED,
                evidence: "Cùng quận/huyện ({$jobLoc->district})",
                details: []
            );
        }

        if ($matchedCity) {
            return new CriterionEvaluation(
                criterionName: CriterionName::LOCATION,
                state: DataState::AVAILABLE,
                score: 60.0,
                weight: self::WEIGHT_LOCATION,
                confidence: 0.8,
                provenance: Provenance::STRUCTURED,
                evidence: "Cùng thành phố ({$jobLoc->city})",
                details: []
            );
        }

        $considerations[] = 'Địa điểm làm việc có thể cách xa khu vực mong muốn của bạn.';
        return new CriterionEvaluation(
            criterionName: CriterionName::LOCATION,
            state: DataState::AVAILABLE,
            score: 25.0,
            weight: self::WEIGHT_LOCATION,
            confidence: 0.7,
            provenance: Provenance::STRUCTURED,
            evidence: 'Khác khu vực mong muốn đã chọn',
            details: []
        );
    }

    private function evaluateRoleRelevance(
        CandidateProfileContract $candidate,
        JobRequirementsContract $job,
        array &$strengths,
        array &$considerations,
        array &$missingData
    ): CriterionEvaluation {
        $jobRole = $job->role;
        if (!$jobRole || empty($jobRole->title)) {
            return new CriterionEvaluation(
                criterionName: CriterionName::ROLE_RELEVANCE,
                state: DataState::NOT_APPLICABLE,
                score: null,
                weight: self::WEIGHT_ROLE_RELEVANCE,
                confidence: 1.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Không có tiêu đề vị trí để so khớp',
                details: []
            );
        }

        $titleKey = SkillNormalizer::generateMatchKey($jobRole->title);

        $hasSkills = !empty($candidate->skills);
        $hasExp = ($candidate->experience && !empty($candidate->experience->items));
        $hasCandidateSignals = ($hasSkills || $hasExp);

        // If candidate has no skills and no experience -> UNKNOWN/null (missing data)
        if (!$hasCandidateSignals) {
            $missingData['role_relevance'] = DataState::UNKNOWN->value;
            return new CriterionEvaluation(
                criterionName: CriterionName::ROLE_RELEVANCE,
                state: DataState::UNKNOWN,
                score: null,
                weight: self::WEIGHT_ROLE_RELEVANCE,
                confidence: 0.0,
                provenance: Provenance::STRUCTURED,
                evidence: 'Hồ sơ chưa có kỹ năng hoặc kinh nghiệm để đánh giá độ phù hợp vai trò',
                details: ['reason' => 'no_candidate_signals']
            );
        }

        // Check if title words match candidate skills or experience
        $score = 0.0;
        $matchedTerms = [];

        foreach ($candidate->skills as $cSkill) {
            $sKey = $cSkill->matchKey ?? SkillNormalizer::generateMatchKey($cSkill->canonicalName);
            if (str_contains($titleKey, $sKey) || str_contains($sKey, $titleKey) || $this->hasSubstantialWordOverlap($titleKey, $sKey, true)) {
                $score = max($score, 85.0);
                $matchedTerms[] = $cSkill->canonicalName;
            }
        }

        if ($candidate->experience && !empty($candidate->experience->items)) {
            foreach ($candidate->experience->items as $item) {
                $rKey = SkillNormalizer::generateMatchKey($item->role);
                if (str_contains($titleKey, $rKey) || str_contains($rKey, $titleKey) || $this->hasSubstantialWordOverlap($titleKey, $rKey, true)) {
                    $score = max($score, 95.0);
                    $matchedTerms[] = $item->role;
                }
            }
        }

        // Candidate HAS signals!
        if (!empty($matchedTerms)) {
            $strengths[] = 'Kinh nghiệm hoặc kỹ năng của bạn liên quan trực tiếp đến vai trò công việc này.';
            return new CriterionEvaluation(
                criterionName: CriterionName::ROLE_RELEVANCE,
                state: DataState::AVAILABLE,
                score: $score,
                weight: self::WEIGHT_ROLE_RELEVANCE,
                confidence: 0.9,
                provenance: Provenance::STRUCTURED,
                evidence: 'Khớp từ khóa vai trò: ' . implode(', ', array_slice($matchedTerms, 0, 2)),
                details: ['matched_terms' => $matchedTerms]
            );
        }

        // Candidate has signals but NO overlap with job role -> AVAILABLE with score 0.0 (unrelated, not missing data!)
        $considerations[] = 'Kỹ năng và kinh nghiệm hiện có trong hồ sơ chưa tương đồng với vai trò công việc này.';
        return new CriterionEvaluation(
            criterionName: CriterionName::ROLE_RELEVANCE,
            state: DataState::AVAILABLE,
            score: 0.0,
            weight: self::WEIGHT_ROLE_RELEVANCE,
            confidence: 0.85,
            provenance: Provenance::STRUCTURED,
            evidence: 'Kỹ năng và kinh nghiệm trong hồ sơ không trùng khớp với vị trí công việc',
            details: ['matched_terms' => []]
        );
    }

    private function isSynonym(string $key1, string $key2): bool
    {
        if (isset(self::SYNONYM_MAP[$key1]) && in_array($key2, self::SYNONYM_MAP[$key1], true)) {
            return true;
        }
        if (isset(self::SYNONYM_MAP[$key2]) && in_array($key1, self::SYNONYM_MAP[$key2], true)) {
            return true;
        }
        return false;
    }

    private const ROLE_STOP_WORDS = [
        'nhan', 'vien', 'chuyen', 'cong', 'tac', 'part', 'time', 'full', 'thuc', 'tap', 'sinh', 'vi', 'tri'
    ];

    private function hasSubstantialWordOverlap(string $text1, string $text2, bool $excludeRoleStopWords = false): bool
    {
        $words1 = array_filter(explode(' ', $text1), fn(string $w) => mb_strlen($w) >= 3);
        $words2 = array_filter(explode(' ', $text2), fn(string $w) => mb_strlen($w) >= 3);

        if ($excludeRoleStopWords) {
            $words1 = array_diff($words1, self::ROLE_STOP_WORDS);
            $words2 = array_diff($words2, self::ROLE_STOP_WORDS);
        }

        if (empty($words1) || empty($words2)) {
            return false;
        }

        $common = array_intersect($words1, $words2);
        return !empty($common);
    }
}
