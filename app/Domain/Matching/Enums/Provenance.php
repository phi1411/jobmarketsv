<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Enums;

use JsonSerializable;

enum Provenance: string implements JsonSerializable
{
    case STRUCTURED = 'structured';
    case SEMANTIC_EXTRACTION = 'semantic_extraction';

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
