<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class EducationValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'major',
        'academic_year',
        'level',
        'evidence',
        'confidence',
    ];

    public function __construct(
        public DataState $state = DataState::AVAILABLE,
        public ?string $major = null,
        public ?int $academicYear = null,
        public ?string $level = null,
        public ?string $evidence = null,
        public float $confidence = 1.0
    ) {
        ValidationGuard::assertStringLength($this->major, 'major', 255, true);
        ValidationGuard::assertStringLength($this->level, 'level', 100, true);
        ValidationGuard::assertStringLength($this->evidence, 'evidence', 1000, true);
        ValidationGuard::assertConfidence($this->confidence, 'confidence');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'education');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'education');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::AVAILABLE->value, DataState::class, 'state');
        $major = ValidationGuard::assertStringLength($data['major'] ?? null, 'major', 255, true);

        $academicYear = null;
        if (isset($data['academic_year']) && $data['academic_year'] !== null) {
            if (!is_int($data['academic_year'])) {
                throw new MatchingContractValidationException(
                    ['academic_year' => 'academic_year phải là số nguyên (int).'],
                    "Sai kiểu dữ liệu cho 'academic_year'"
                );
            }
            if ($data['academic_year'] < 1 || $data['academic_year'] > 10) {
                throw new MatchingContractValidationException(
                    ['academic_year' => 'academic_year phải từ 1 đến 10.'],
                    "Giá trị không hợp lệ cho 'academic_year'"
                );
            }
            $academicYear = $data['academic_year'];
        }

        $level = ValidationGuard::assertStringLength($data['level'] ?? null, 'level', 100, true);
        $evidence = ValidationGuard::assertStringLength($data['evidence'] ?? null, 'evidence', 1000, true);
        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 1.0;

        return new self(
            state: $state,
            major: $major,
            academicYear: $academicYear,
            level: $level,
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
            'major' => $this->major,
            'academic_year' => $this->academicYear,
            'level' => $this->level,
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
