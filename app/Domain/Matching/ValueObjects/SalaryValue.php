<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class SalaryValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'type',
        'min',
        'max',
        'currency',
    ];

    public function __construct(
        public DataState $state = DataState::UNKNOWN,
        public ?string $type = null,
        public float|int|null $min = null,
        public float|int|null $max = null,
        public ?string $currency = 'VND'
    ) {
        if ($this->state === DataState::AVAILABLE && $this->min === null && $this->max === null && $this->type === null) {
            throw new MatchingContractValidationException(
                ['salary' => 'Trạng thái AVAILABLE yêu cầu phải có min, max hoặc type.'],
                'Trạng thái AVAILABLE thiếu dữ liệu lương thực tế'
            );
        }
        if ($this->state !== DataState::AVAILABLE) {
            if ($this->min !== null || $this->max !== null) {
                throw new MatchingContractValidationException(
                    ['salary' => "Trạng thái {$this->state->value} không được mang giá trị min hoặc max lương."],
                    "Mâu thuẫn dữ liệu lương khi trạng thái là {$this->state->value}"
                );
            }
        }

        ValidationGuard::assertStringLength($this->type, 'type', 50, true);
        ValidationGuard::assertStringLength($this->currency, 'currency', 10, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'salary');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'salary');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::UNKNOWN->value, DataState::class, 'state');
        $type = ValidationGuard::assertStringLength($data['type'] ?? null, 'type', 50, true);

        $min = null;
        if (isset($data['min']) && $data['min'] !== null) {
            if (!is_int($data['min']) && !is_float($data['min'])) {
                throw new MatchingContractValidationException(
                    ['min' => "Trường 'min' phải là số."],
                    "Sai kiểu dữ liệu cho 'min'"
                );
            }
            if ($data['min'] < 0) {
                throw new MatchingContractValidationException(
                    ['min' => "Lương tối thiểu 'min' không được âm."],
                    "Giá trị không hợp lệ cho 'min'"
                );
            }
            $min = $data['min'];
        }

        $max = null;
        if (isset($data['max']) && $data['max'] !== null) {
            if (!is_int($data['max']) && !is_float($data['max'])) {
                throw new MatchingContractValidationException(
                    ['max' => "Trường 'max' phải là số."],
                    "Sai kiểu dữ liệu cho 'max'"
                );
            }
            if ($data['max'] < 0) {
                throw new MatchingContractValidationException(
                    ['max' => "Lương tối đa 'max' không được âm."],
                    "Giá trị không hợp lệ cho 'max'"
                );
            }
            $max = $data['max'];
        }

        if ($min !== null && $max !== null && $min > $max) {
            throw new MatchingContractValidationException(
                ['min' => "Lương tối thiểu 'min' ({$min}) không được lớn hơn lương tối đa 'max' ({$max})."],
                "Khoảng lương không hợp lệ"
            );
        }

        $currency = ValidationGuard::assertStringLength($data['currency'] ?? 'VND', 'currency', 10, true);

        return new self(
            state: $state,
            type: $type,
            min: $min,
            max: $max,
            currency: $currency
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'type' => $this->type,
            'min' => $this->min,
            'max' => $this->max,
            'currency' => $this->currency,
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
