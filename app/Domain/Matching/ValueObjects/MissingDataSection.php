<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class MissingDataSection implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'items',
    ];

    /**
     * @param list<mixed> $items
     */
    public function __construct(
        public DataState $state = DataState::NOT_AVAILABLE,
        public array $items = []
    ) {
        if ($this->state === DataState::AVAILABLE && empty($this->items)) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['items' => "MissingDataSection ở trạng thái 'AVAILABLE' không được có danh sách items rỗng."],
                "Danh sách items không được rỗng khi trạng thái là AVAILABLE"
            );
        }
        ValidationGuard::assertArrayLimit($this->items, 'items', 50);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'missing_data_section');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'missing_data_section');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::NOT_AVAILABLE->value, DataState::class, 'state');
        $items = ValidationGuard::assertArrayLimit($data['items'] ?? [], 'items', 50);

        return new self(state: $state, items: $items);
    }

    /**
     * Factory for NOT_AVAILABLE state
     */
    public static function notAvailable(): self
    {
        return new self(state: DataState::NOT_AVAILABLE, items: []);
    }

    /**
     * Factory for UNKNOWN state
     */
    public static function unknown(): self
    {
        return new self(state: DataState::UNKNOWN, items: []);
    }

    /**
     * Factory for NOT_APPLICABLE state
     */
    public static function notApplicable(): self
    {
        return new self(state: DataState::NOT_APPLICABLE, items: []);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'items' => $this->items,
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
