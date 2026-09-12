<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Enums;

use JsonSerializable;

enum MatchClassification: string implements JsonSerializable
{
    case HIGH_MATCH = 'HIGH_MATCH';
    case GOOD_MATCH = 'GOOD_MATCH';
    case REVIEW_NEEDED = 'REVIEW_NEEDED';
    case INSUFFICIENT_DATA = 'INSUFFICIENT_DATA';

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
