<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class SkillItem implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'canonical_name',
        'source_skill_id',
        'match_key',
        'evidence',
        'confidence',
        'provenance',
        'importance',
    ];

    public function __construct(
        public string $canonicalName,
        public ?string $sourceSkillId = null,
        public ?string $matchKey = null,
        public ?string $evidence = null,
        public float $confidence = 1.0,
        public Provenance $provenance = Provenance::STRUCTURED,
        public ?string $importance = null
    ) {
        ValidationGuard::assertStringLength($this->canonicalName, 'canonical_name', 255);
        ValidationGuard::assertNoPlaceholder($this->canonicalName, 'canonical_name');
        ValidationGuard::assertStringLength($this->sourceSkillId, 'source_skill_id', 64, true);
        ValidationGuard::assertStringLength($this->matchKey, 'match_key', 255, true);
        ValidationGuard::assertStringLength($this->evidence, 'evidence', 1000, true);
        ValidationGuard::assertConfidence($this->confidence, 'confidence');
        ValidationGuard::assertStringLength($this->importance, 'importance', 32, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'skill_item');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'skill_item');
        }

        $canonicalName = ValidationGuard::assertStringLength($data['canonical_name'] ?? null, 'canonical_name', 255);
        $sourceSkillId = ValidationGuard::assertStringLength($data['source_skill_id'] ?? null, 'source_skill_id', 64, true);
        $matchKey = ValidationGuard::assertStringLength($data['match_key'] ?? null, 'match_key', 255, true);
        $evidence = ValidationGuard::assertStringLength($data['evidence'] ?? null, 'evidence', 1000, true);

        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 1.0;

        /** @var Provenance $provenance */
        $provenance = isset($data['provenance'])
            ? ValidationGuard::assertEnum($data['provenance'], Provenance::class, 'provenance')
            : Provenance::STRUCTURED;

        $importance = ValidationGuard::assertStringLength($data['importance'] ?? null, 'importance', 32, true);

        return new self(
            canonicalName: $canonicalName,
            sourceSkillId: $sourceSkillId,
            matchKey: $matchKey,
            evidence: $evidence,
            confidence: $confidence,
            provenance: $provenance,
            importance: $importance
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arr = [
            'canonical_name' => $this->canonicalName,
            'source_skill_id' => $this->sourceSkillId,
            'match_key' => $this->matchKey,
            'evidence' => $this->evidence,
            'confidence' => $this->confidence,
            'provenance' => $this->provenance->value,
        ];

        if ($this->importance !== null) {
            $arr['importance'] = $this->importance;
        }

        return $arr;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
