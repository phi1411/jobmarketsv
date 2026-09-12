<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DayOfWeek;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class ScheduleSlot implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'day',
        'shift',
    ];

    public function __construct(
        public DayOfWeek $day,
        public ShiftType $shift
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'schedule_slot');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'schedule_slot');
        }

        /** @var DayOfWeek $day */
        $day = ValidationGuard::assertEnum($data['day'] ?? null, DayOfWeek::class, 'day');
        /** @var ShiftType $shift */
        $shift = ValidationGuard::assertEnum($data['shift'] ?? null, ShiftType::class, 'shift');

        return new self(day: $day, shift: $shift);
    }

    /**
     * @return array{day: string, shift: string}
     */
    public function toArray(): array
    {
        return [
            'day' => $this->day->value,
            'shift' => $this->shift->value,
        ];
    }

    /**
     * @return array{day: string, shift: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
