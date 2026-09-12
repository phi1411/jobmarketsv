<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class ExperienceItem implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'role',
        'duration_months',
        'evidence',
        'confidence',
        'provenance',
    ];

    public function __construct(
        public string $role,
        public ?int $durationMonths = null,
        public ?string $evidence = null,
        public float $confidence = 1.0,
        public Provenance $provenance = Provenance::STRUCTURED
    ) {
        ValidationGuard::assertStringLength($this->role, 'role', 255);
        ValidationGuard::assertNoPlaceholder($this->role, 'role');
        ValidationGuard::assertStringLength($this->evidence, 'evidence', 1000, true);
        ValidationGuard::assertConfidence($this->confidence, 'confidence');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'experience_item');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'experience_item');
        }

        $role = ValidationGuard::assertStringLength($data['role'] ?? null, 'role', 255);

        $durationMonths = null;
        if (isset($data['duration_months']) && $data['duration_months'] !== null) {
            if (!is_int($data['duration_months'])) {
                throw new MatchingContractValidationException(
                    ['duration_months' => 'duration_months phải là số nguyên (int).'],
                    "Sai kiểu dữ liệu cho 'duration_months'"
                );
            }
            if ($data['duration_months'] < 0) {
                throw new MatchingContractValidationException(
                    ['duration_months' => 'duration_months không được âm.'],
                    "Giá trị không hợp lệ cho 'duration_months'"
                );
            }
            $durationMonths = $data['duration_months'];
        }

        $evidence = ValidationGuard::assertStringLength($data['evidence'] ?? null, 'evidence', 1000, true);
        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 1.0;

        /** @var Provenance $provenance */
        $provenance = isset($data['provenance'])
            ? ValidationGuard::assertEnum($data['provenance'], Provenance::class, 'provenance')
            : Provenance::STRUCTURED;

        return new self(
            role: $role,
            durationMonths: $durationMonths,
            evidence: $evidence,
            confidence: $confidence,
            provenance: $provenance
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'duration_months' => $this->durationMonths,
            'evidence' => $this->evidence,
            'confidence' => $this->confidence,
            'provenance' => $this->provenance->value,
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
