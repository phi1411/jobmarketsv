<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\CriterionName;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class CriterionEvaluation implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'criterion_name',
        'state',
        'score',
        'weight',
        'confidence',
        'provenance',
        'evidence',
        'details',
    ];

    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public CriterionName $criterionName,
        public DataState $state = DataState::AVAILABLE,
        public ?float $score = null,
        public float $weight = 0.0,
        public float $confidence = 1.0,
        public Provenance $provenance = Provenance::STRUCTURED,
        public ?string $evidence = null,
        public array $details = []
    ) {
        if ($this->state === DataState::AVAILABLE) {
            if ($this->score === null) {
                throw new MatchingContractValidationException(
                    ['score' => "Criterion ở trạng thái 'AVAILABLE' bắt buộc phải có điểm (score không được là null)."],
                    "Thiếu điểm cho tiêu chí AVAILABLE"
                );
            }
            ValidationGuard::assertScore($this->score, 'score');
        } else {
            if ($this->score !== null) {
                throw new MatchingContractValidationException(
                    ['score' => "Criterion ở trạng thái '{$this->state->value}' không được có điểm (score phải là null)."],
                    "Điểm không hợp lệ cho tiêu chí không AVAILABLE"
                );
            }
        }

        if ($this->weight < 0.0 || $this->weight > 100.0) {
            throw new MatchingContractValidationException(
                ['weight' => "Weight phải từ 0.0 đến 100.0. Nhận được: {$this->weight}"],
                "Weight vượt giới hạn cho phép"
            );
        }

        ValidationGuard::assertConfidence($this->confidence, 'confidence');
        ValidationGuard::assertStringLength($this->evidence, 'evidence', 1000, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'criterion_evaluation');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'criterion_evaluation');
        }

        /** @var CriterionName $cName */
        $cName = ValidationGuard::assertEnum($data['criterion_name'] ?? null, CriterionName::class, 'criterion_name');
        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::AVAILABLE->value, DataState::class, 'state');

        $score = null;
        if (isset($data['score']) && $data['score'] !== null) {
            $score = ValidationGuard::assertScore($data['score'], 'score');
        }

        $weight = 0.0;
        if (isset($data['weight'])) {
            if (!is_int($data['weight']) && !is_float($data['weight'])) {
                throw new MatchingContractValidationException(
                    ['weight' => "Weight phải là số (int hoặc float)."],
                    "Sai kiểu dữ liệu cho 'weight'"
                );
            }
            $weight = (float)$data['weight'];
        }

        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 1.0;

        /** @var Provenance $provenance */
        $provenance = isset($data['provenance'])
            ? ValidationGuard::assertEnum($data['provenance'], Provenance::class, 'provenance')
            : Provenance::STRUCTURED;

        $evidence = ValidationGuard::assertStringLength($data['evidence'] ?? null, 'evidence', 1000, true);
        $rawDetails = $data['details'] ?? [];
        if (!is_array($rawDetails)) {
            ValidationGuard::assertArrayLimit($rawDetails, 'details');
        }
        ValidationGuard::assertNoPii($rawDetails, 'criterion_details');

        return new self(
            criterionName: $cName,
            state: $state,
            score: $score,
            weight: $weight,
            confidence: $confidence,
            provenance: $provenance,
            evidence: $evidence,
            details: $rawDetails
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'criterion_name' => $this->criterionName->value,
            'state' => $this->state->value,
            'score' => $this->score,
            'weight' => $this->weight,
            'confidence' => $this->confidence,
            'provenance' => $this->provenance->value,
            'evidence' => $this->evidence,
            'details' => $this->details,
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
