<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Enums;

use JsonSerializable;

enum CriterionName: string implements JsonSerializable
{
    case AGE = 'age';
    case SKILLS = 'skills';
    case AVAILABILITY = 'availability';
    case EXPERIENCE = 'experience';
    case EDUCATION = 'education';
    case LOCATION = 'location';
    case ROLE_RELEVANCE = 'role_relevance';
    case SALARY = 'salary';

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
