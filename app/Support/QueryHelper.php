<?php

namespace JobMarket\Support;

class QueryHelper
{
    /**
     * Sanitize and validate sort column and direction against whitelist
     */
    public static function sanitizeSort(
        ?string $sortBy,
        ?string $sortDir,
        array $allowedColumns,
        string $defaultColumn = "created_at",
        string $defaultDir = "DESC"
    ): array {
        $cleanColumn = $defaultColumn;
        if ($sortBy !== null && in_array(strtolower(trim($sortBy)), array_map("strtolower", $allowedColumns), true)) {
            $cleanColumn = strtolower(trim($sortBy));
        }

        $cleanDir = strtoupper(trim((string)$sortDir));
        if ($cleanDir !== "ASC" && $cleanDir !== "DESC") {
            $cleanDir = $defaultDir;
        }

        return [
            "column"    => $cleanColumn,
            "direction" => $cleanDir,
            "sql"       => "ORDER BY `{$cleanColumn}` {$cleanDir}"
        ];
    }

    /**
     * Escape strings safely for SQL LIKE queries (avoids % and _ wildcards abuse)
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
