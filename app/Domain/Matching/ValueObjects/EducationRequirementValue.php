<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class EducationRequirementValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'levels',
        'majors',
        'evidence',
        'confidence',
    ];

    /**
     * @param list<string> $levels
     * @param list<string> $majors
     */
    public function __construct(
        public DataState $state = DataState::UNKNOWN,
        public array $levels = [],
        public array $majors = [],
        public ?string $evidence = null,
        public float $confidence = 0.0
    ) {
        if ($this->state === DataState::AVAILABLE && empty($this->levels) && empty($this->majors) && empty($this->evidence)) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['education_requirement' => 'Trạng thái AVAILABLE yêu cầu phải có levels, majors hoặc evidence.'],
                'Trạng thái AVAILABLE thiếu dữ liệu học vấn thực tế'
            );
        }
        if ($this->state !== DataState::AVAILABLE) {
            if (!empty($this->levels) || !empty($this->majors)) {
                throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                    ['education_requirement' => "Trạng thái {$this->state->value} không được mang danh sách levels hoặc majors."],
                    "Mâu thuẫn dữ liệu học vấn khi trạng thái là {$this->state->value}"
                );
            }
        }

        ValidationGuard::assertArrayLimit($this->levels, 'levels', 20);
        ValidationGuard::assertArrayLimit($this->majors, 'majors', 50);
        ValidationGuard::assertStringLength($this->evidence, 'evidence', 1000, true);
        ValidationGuard::assertConfidence($this->confidence, 'confidence');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'education_requirement');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'education_requirement');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::UNKNOWN->value, DataState::class, 'state');

        $rawLevels = ValidationGuard::assertArrayLimit($data['levels'] ?? [], 'levels', 20);
        $levels = [];
        foreach ($rawLevels as $lvl) {
            $levels[] = ValidationGuard::assertStringLength($lvl, 'level_item', 100);
        }

        $rawMajors = ValidationGuard::assertArrayLimit($data['majors'] ?? [], 'majors', 50);
        $majors = [];
        foreach ($rawMajors as $m) {
            $majors[] = ValidationGuard::assertStringLength($m, 'major_item', 255);
        }

        $evidence = ValidationGuard::assertStringLength($data['evidence'] ?? null, 'evidence', 1000, true);
        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 0.0;

        return new self(
            state: $state,
            levels: $levels,
            majors: $majors,
            evidence: $evidence,
            confidence: $confidence
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'levels' => $this->levels,
            'majors' => $this->majors,
            'evidence' => $this->evidence,
            'confidence' => $this->confidence,
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
