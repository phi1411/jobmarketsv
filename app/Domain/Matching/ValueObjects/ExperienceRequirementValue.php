<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class ExperienceRequirementValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'minimum_months',
        'domains',
        'evidence',
        'confidence',
    ];

    /**
     * @param list<string> $domains
     */
    public function __construct(
        public DataState $state = DataState::UNKNOWN,
        public ?int $minimumMonths = null,
        public array $domains = [],
        public ?string $evidence = null,
        public float $confidence = 1.0
    ) {
        if ($this->state === DataState::AVAILABLE && $this->minimumMonths === null && empty($this->domains) && empty($this->evidence)) {
            throw new MatchingContractValidationException(
                ['experience_requirement' => 'Trạng thái AVAILABLE yêu cầu phải có minimum_months, domains hoặc evidence.'],
                'Trạng thái AVAILABLE thiếu dữ liệu thực tế'
            );
        }
        if ($this->state !== DataState::AVAILABLE) {
            if ($this->minimumMonths !== null) {
                throw new MatchingContractValidationException(
                    ['minimum_months' => "Trạng thái {$this->state->value} không được mang giá trị minimum_months ({$this->minimumMonths})."],
                    "Mâu thuẫn dữ liệu kinh nghiệm khi trạng thái là {$this->state->value}"
                );
            }
            if (!empty($this->domains)) {
                throw new MatchingContractValidationException(
                    ['domains' => "Trạng thái {$this->state->value} không được mang danh sách domains."],
                    "Mâu thuẫn dữ liệu kinh nghiệm khi trạng thái là {$this->state->value}"
                );
            }
        }

        ValidationGuard::assertArrayLimit($this->domains, 'domains', 50);
        ValidationGuard::assertStringLength($this->evidence, 'evidence', 1000, true);
        ValidationGuard::assertConfidence($this->confidence, 'confidence');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'experience_requirement');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'experience_requirement');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::UNKNOWN->value, DataState::class, 'state');

        $minMonths = null;
        if (isset($data['minimum_months']) && $data['minimum_months'] !== null) {
            if (!is_int($data['minimum_months'])) {
                throw new MatchingContractValidationException(
                    ['minimum_months' => 'minimum_months phải là số nguyên (int).'],
                    "Sai kiểu dữ liệu cho 'minimum_months'"
                );
            }
            if ($data['minimum_months'] < 0) {
                throw new MatchingContractValidationException(
                    ['minimum_months' => 'minimum_months không được âm.'],
                    "Giá trị không hợp lệ cho 'minimum_months'"
                );
            }
            $minMonths = $data['minimum_months'];
        }

        $rawDomains = ValidationGuard::assertArrayLimit($data['domains'] ?? [], 'domains', 50);
        $domains = [];
        foreach ($rawDomains as $d) {
            $domains[] = ValidationGuard::assertStringLength($d, 'domain_item', 100);
        }

        $evidence = ValidationGuard::assertStringLength($data['evidence'] ?? null, 'evidence', 1000, true);
        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 1.0;

        return new self(
            state: $state,
            minimumMonths: $minMonths,
            domains: $domains,
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
            'minimum_months' => $this->minimumMonths,
            'domains' => $this->domains,
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
