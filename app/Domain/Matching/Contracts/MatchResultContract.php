<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Contracts;

use JobMarket\Domain\Matching\Enums\MatchClassification;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JobMarket\Domain\Matching\ValueObjects\CriterionEvaluation;
use JsonSerializable;

final readonly class MatchResultContract implements JsonSerializable
{
    public const SCHEMA_VERSION = 'match-result.v1';

    public const DEFAULT_DISCLAIMER = 'Điểm này chỉ phản ánh mức độ khớp giữa dữ liệu hồ sơ hiện có và nội dung tin tuyển dụng. Đây không phải xác suất được tuyển, không thay thế đánh giá của nhà tuyển dụng và không tự động chấp nhận hoặc từ chối hồ sơ.';

    public const ALLOWED_KEYS = [
        'schema_version',
        'candidate_id',
        'job_id',
        'overall_score',
        'coverage_percent',
        'classification',
        'criteria',
        'strengths',
        'considerations',
        'missing_data',
        'disclaimer',
    ];

    /**
     * @param list<CriterionEvaluation> $criteria
     * @param list<string> $strengths
     * @param list<string> $considerations
     * @param array<string, mixed> $missingData
     */
    public function __construct(
        public string $schemaVersion = self::SCHEMA_VERSION,
        public ?string $candidateId = null,
        public ?string $jobId = null,
        public ?float $overallScore = null,
        public float $coveragePercent = 0.0,
        public MatchClassification $classification = MatchClassification::INSUFFICIENT_DATA,
        public array $criteria = [],
        public array $strengths = [],
        public array $considerations = [],
        public array $missingData = [],
        public string $disclaimer = self::DEFAULT_DISCLAIMER
    ) {
        if ($this->schemaVersion !== self::SCHEMA_VERSION) {
            throw new MatchingContractValidationException(
                ['schema_version' => "Schema version '{$this->schemaVersion}' không hợp lệ. Yêu cầu: '" . self::SCHEMA_VERSION . "'."],
                "Schema version không hợp lệ"
            );
        }
        ValidationGuard::assertStringLength($this->schemaVersion, 'schema_version', 32);
        ValidationGuard::assertStringLength($this->candidateId, 'candidate_id', 64, true);
        ValidationGuard::assertStringLength($this->jobId, 'job_id', 64, true);

        if ($this->overallScore !== null) {
            ValidationGuard::assertScore($this->overallScore, 'overall_score');
        }

        if ($this->coveragePercent < 0.0 || $this->coveragePercent > 100.0) {
            throw new MatchingContractValidationException(
                ['coverage_percent' => "coverage_percent phải từ 0.0 đến 100.0. Nhận được: {$this->coveragePercent}"],
                "Coverage percent vượt giới hạn [0, 100]"
            );
        }

        ValidationGuard::assertArrayLimit($this->criteria, 'criteria', 50);
        ValidationGuard::assertArrayLimit($this->strengths, 'strengths', 50);
        ValidationGuard::assertArrayLimit($this->considerations, 'considerations', 50);
        ValidationGuard::assertStringLength($this->disclaimer, 'disclaimer', 1000);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'match_result');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'match_result');
        }

        $schemaVersion = ValidationGuard::assertStringLength(
            $data['schema_version'] ?? self::SCHEMA_VERSION,
            'schema_version',
            32
        );
        $candidateId = ValidationGuard::assertStringLength($data['candidate_id'] ?? null, 'candidate_id', 64, true);
        $jobId = ValidationGuard::assertStringLength($data['job_id'] ?? null, 'job_id', 64, true);

        $overallScore = null;
        if (isset($data['overall_score']) && $data['overall_score'] !== null) {
            $overallScore = ValidationGuard::assertScore($data['overall_score'], 'overall_score');
        }

        $coveragePercent = 0.0;
        if (isset($data['coverage_percent'])) {
            if (!is_int($data['coverage_percent']) && !is_float($data['coverage_percent'])) {
                throw new MatchingContractValidationException(
                    ['coverage_percent' => 'coverage_percent phải là số (float hoặc int).'],
                    "Sai kiểu dữ liệu cho 'coverage_percent'"
                );
            }
            $coveragePercent = (float)$data['coverage_percent'];
            if ($coveragePercent < 0.0 || $coveragePercent > 100.0) {
                throw new MatchingContractValidationException(
                    ['coverage_percent' => "coverage_percent phải từ 0.0 đến 100.0. Nhận được: {$coveragePercent}"],
                    "Coverage percent vượt giới hạn [0, 100]"
                );
            }
        }

        /** @var MatchClassification $classification */
        $classification = ValidationGuard::assertEnum(
            $data['classification'] ?? MatchClassification::INSUFFICIENT_DATA->value,
            MatchClassification::class,
            'classification'
        );

        $rawCriteria = ValidationGuard::assertArrayLimit($data['criteria'] ?? [], 'criteria', 50);
        $criteria = [];
        foreach ($rawCriteria as $crit) {
            if ($crit instanceof CriterionEvaluation) {
                $criteria[] = $crit;
            } elseif (is_array($crit)) {
                $criteria[] = CriterionEvaluation::fromArray($crit, $strict);
            } else {
                ValidationGuard::assertArrayLimit($crit, 'criterion_item');
            }
        }

        $rawStrengths = ValidationGuard::assertArrayLimit($data['strengths'] ?? [], 'strengths', 50);
        $strengths = [];
        foreach ($rawStrengths as $s) {
            $strengths[] = ValidationGuard::assertStringLength($s, 'strength_item', 500);
        }

        $rawConsiderations = ValidationGuard::assertArrayLimit($data['considerations'] ?? [], 'considerations', 50);
        $considerations = [];
        foreach ($rawConsiderations as $c) {
            $considerations[] = ValidationGuard::assertStringLength($c, 'consideration_item', 500);
        }

        $missingData = $data['missing_data'] ?? [];
        if (!is_array($missingData)) {
            ValidationGuard::assertArrayLimit($missingData, 'missing_data');
        }

        $disclaimer = ValidationGuard::assertStringLength(
            $data['disclaimer'] ?? self::DEFAULT_DISCLAIMER,
            'disclaimer',
            1000
        );

        return new self(
            schemaVersion: $schemaVersion,
            candidateId: $candidateId,
            jobId: $jobId,
            overallScore: $overallScore,
            coveragePercent: $coveragePercent,
            classification: $classification,
            criteria: $criteria,
            strengths: $strengths,
            considerations: $considerations,
            missingData: $missingData,
            disclaimer: $disclaimer
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'candidate_id' => $this->candidateId,
            'job_id' => $this->jobId,
            'overall_score' => $this->overallScore,
            'coverage_percent' => $this->coveragePercent,
            'classification' => $this->classification->value,
            'criteria' => array_map(fn(CriterionEvaluation $c) => $c->toArray(), $this->criteria),
            'strengths' => $this->strengths,
            'considerations' => $this->considerations,
            'missing_data' => $this->missingData,
            'disclaimer' => $this->disclaimer,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
