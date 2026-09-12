<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class ExperienceValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'items',
    ];

    /**
     * @param list<ExperienceItem> $items
     */
    public function __construct(
        public DataState $state = DataState::AVAILABLE,
        public array $items = []
    ) {
        if ($this->state === DataState::AVAILABLE && empty($this->items)) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['items' => "ExperienceValue ở trạng thái 'AVAILABLE' không được có danh sách items rỗng."],
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
        ValidationGuard::assertNoPii($data, 'experience');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'experience');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::AVAILABLE->value, DataState::class, 'state');

        $rawItems = ValidationGuard::assertArrayLimit($data['items'] ?? [], 'items', 50);
        $items = [];
        foreach ($rawItems as $item) {
            if ($item instanceof ExperienceItem) {
                $items[] = $item;
            } elseif (is_array($item)) {
                $items[] = ExperienceItem::fromArray($item, $strict);
            } else {
                ValidationGuard::assertArrayLimit($item, 'experience_item');
            }
        }

        return new self(state: $state, items: $items);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'items' => array_map(fn(ExperienceItem $item) => $item->toArray(), $this->items),
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
