<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Enums;

use JsonSerializable;

enum ShiftType: string implements JsonSerializable
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';
    case FLEXIBLE = 'flexible';
    case ROTATING = 'rotating';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
