<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Enums;

use JsonSerializable;

enum DayOfWeek: string implements JsonSerializable
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

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
