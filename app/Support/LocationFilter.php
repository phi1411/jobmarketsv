<?php

namespace JobMarket\Support;

final class LocationFilter
{
    /**
     * Accepts either a comma-separated query value or a JSON array.
     * Invalid identifiers are ignored so they can never become SQL fragments.
     *
     * @return list<string>
     */
    public static function normalizeIds(mixed $value, int $maxItems = 20): array
    {
        if (is_string($value)) {
            $value = explode(",", $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $id = trim((string)$item);
            if ($id === "" || !preg_match('/^[0-9a-zA-Z_-]{1,64}$/', $id)) {
                continue;
            }
            $ids[$id] = true;
            if (count($ids) >= $maxItems) {
                break;
            }
        }

        return array_keys($ids);
    }
}
