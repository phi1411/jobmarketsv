<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Enums;

use JsonSerializable;

enum DataState: string implements JsonSerializable
{
    case AVAILABLE = 'AVAILABLE';
    case UNKNOWN = 'UNKNOWN';
    case NOT_APPLICABLE = 'NOT_APPLICABLE';
    case NOT_AVAILABLE = 'NOT_AVAILABLE';

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
