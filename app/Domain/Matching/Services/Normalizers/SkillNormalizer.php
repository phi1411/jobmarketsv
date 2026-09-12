<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services\Normalizers;

use JobMarket\Domain\Matching\Enums\Provenance;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Domain\Matching\ValueObjects\SkillItem;

final class SkillNormalizer
{
    /**
     * @param array<string, string> $skillDictionary id => canonical_name map
     */
    public function __construct(
        private array $skillDictionary = []
    ) {
    }

    /**
     * Normalizes candidate skills from skill_ids (JSON/array) and/or free-text skills.
     *
     * @param mixed $skillIds Array or JSON string of skill IDs
     * @param ?string $skillsText Free-text skills string
     * @return list<SkillItem>
     */
    public function normalizeCandidateSkills(mixed $skillIds, ?string $skillsText = null): array
    {
        $resolved = [];
        $seenKeys = [];

        // 1. Process structured skill_ids
        $ids = $this->parseArrayOrJson($skillIds);
        foreach ($ids as $rawId) {
            $id = trim((string)$rawId);
            if ($id === '') {
                continue;
            }

            if (isset($this->skillDictionary[$id])) {
                $canonicalName = $this->skillDictionary[$id];
                $matchKey = self::generateMatchKey($canonicalName);

                if (!isset($seenKeys[$matchKey])) {
                    $seenKeys[$matchKey] = true;
                    $resolved[] = new SkillItem(
                        canonicalName: $canonicalName,
                        sourceSkillId: $id,
                        matchKey: $matchKey,
                        evidence: "student_profiles.skill_ids:{$id}",
                        confidence: 1.0,
                        provenance: Provenance::STRUCTURED,
                        importance: null
                    );
                }
            }
            // Unknown skill IDs are NOT silently treated as canonical skills!
        }

        // 2. Process free-text skills fallback
        if (!empty($skillsText)) {
            $tokens = preg_split('/[,;\n]+/', $skillsText) ?: [];
            foreach ($tokens as $token) {
                if (PiiRedactor::isPlaceholderOrContact($token)) {
                    continue;
                }
                $cleanToken = PiiRedactor::stripPlaceholdersAndContact($token);
                $trimmed = trim($cleanToken);
                if ($trimmed === '' || mb_strlen($trimmed) < 2 || mb_strlen($trimmed) > 100) {
                    continue;
                }
                if (PiiRedactor::containsPlaceholder($trimmed)) {
                    continue;
                }

                $matchKey = self::generateMatchKey($trimmed);
                if (isset($seenKeys[$matchKey])) {
                    continue;
                }

                // Check if text matches any canonical name in dictionary
                $matchedId = null;
                $canonicalName = $trimmed;
                foreach ($this->skillDictionary as $dId => $dName) {
                    if (self::generateMatchKey($dName) === $matchKey) {
                        $matchedId = $dId;
                        $canonicalName = $dName;
                        break;
                    }
                }

                $seenKeys[$matchKey] = true;
                $resolved[] = new SkillItem(
                    canonicalName: $canonicalName,
                    sourceSkillId: $matchedId,
                    matchKey: $matchKey,
                    evidence: "student_profiles.skills:{$trimmed}",
                    confidence: $matchedId !== null ? 0.95 : 0.85,
                    provenance: $matchedId !== null ? Provenance::STRUCTURED : Provenance::SEMANTIC_EXTRACTION,
                    importance: null
                );
            }
        }

        return $resolved;
    }

    /**
     * Normalizes job required skills from mixed representation:
     * - JSON array of skill IDs
     * - Comma-separated skill names
     * - Mixed legacy values
     *
     * @param mixed $rawRequiredSkills
     * @return list<SkillItem>
     */
    public function normalizeJobSkills(mixed $rawRequiredSkills): array
    {
        $resolved = [];
        $seenKeys = [];

        if (empty($rawRequiredSkills)) {
            return [];
        }

        // Check if it's already an array or valid JSON array
        $items = $this->parseArrayOrJson($rawRequiredSkills);

        if (empty($items) && is_string($rawRequiredSkills)) {
            // Treat as comma/newline-separated string
            $items = preg_split('/[,;\n]+/', $rawRequiredSkills) ?: [];
        }

        foreach ($items as $item) {
            $rawItem = trim((string)$item);
            if (PiiRedactor::isPlaceholderOrContact($rawItem)) {
                continue;
            }
            $token = PiiRedactor::stripPlaceholdersAndContact($rawItem);
            if ($token === '' || mb_strlen($token) < 2 || mb_strlen($token) > 100) {
                continue;
            }
            if (PiiRedactor::containsPlaceholder($token)) {
                continue;
            }

            // Check if token is a known skill ID
            if (isset($this->skillDictionary[$token])) {
                $canonicalName = $this->skillDictionary[$token];
                $matchKey = self::generateMatchKey($canonicalName);

                if (!isset($seenKeys[$matchKey])) {
                    $seenKeys[$matchKey] = true;
                    $resolved[] = new SkillItem(
                        canonicalName: $canonicalName,
                        sourceSkillId: $token,
                        matchKey: $matchKey,
                        evidence: "required_skills:{$token}",
                        confidence: 1.0,
                        provenance: Provenance::STRUCTURED,
                        importance: 'required'
                    );
                }
                continue;
            }

            // Otherwise, token is treated as a skill name
            $matchKey = self::generateMatchKey($token);
            if (isset($seenKeys[$matchKey])) {
                continue;
            }

            $matchedId = null;
            $canonicalName = $token;
            foreach ($this->skillDictionary as $dId => $dName) {
                if (self::generateMatchKey($dName) === $matchKey) {
                    $matchedId = $dId;
                    $canonicalName = $dName;
                    break;
                }
            }

            $seenKeys[$matchKey] = true;
            $resolved[] = new SkillItem(
                canonicalName: $canonicalName,
                sourceSkillId: $matchedId,
                matchKey: $matchKey,
                evidence: "required_skills:{$token}",
                confidence: $matchedId !== null ? 1.0 : 0.9,
                provenance: $matchedId !== null ? Provenance::STRUCTURED : Provenance::SEMANTIC_EXTRACTION,
                importance: 'required'
            );
        }

        return $resolved;
    }

    /**
     * Generates a normalized match key:
     * lowercased, trimmed, collapses whitespace, strips accents for diacritic-insensitive matching.
     */
    public static function generateMatchKey(string $text): string
    {
        $clean = mb_strtolower(trim($text), 'UTF-8');
        // Normalize multiple whitespaces
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;
        // Vietnamese accent removal for matching key
        $clean = self::removeVietnameseAccents($clean);
        return trim($clean);
    }

    private static function removeVietnameseAccents(string $str): string
    {
        $accents = [
            'à'=>'a', 'á'=>'a', 'ạ'=>'a', 'ả'=>'a', 'ã'=>'a', 'â'=>'a', 'ầ'=>'a', 'ấ'=>'a', 'ậ'=>'a', 'ẩ'=>'a', 'ẫ'=>'a', 'ă'=>'a', 'ằ'=>'a', 'ắ'=>'a', 'ặ'=>'a', 'ẳ'=>'a', 'ẵ'=>'a',
            'è'=>'e', 'é'=>'e', 'ẹ'=>'e', 'ẻ'=>'e', 'ẽ'=>'e', 'ê'=>'e', 'ề'=>'e', 'ế'=>'e', 'ệ'=>'e', 'ể'=>'e', 'ễ'=>'e',
            'ì'=>'i', 'í'=>'i', 'ị'=>'i', 'ỉ'=>'i', 'ĩ'=>'i',
            'ò'=>'o', 'ó'=>'o', 'ọ'=>'o', 'ỏ'=>'o', 'õ'=>'o', 'ô'=>'o', 'ồ'=>'o', 'ố'=>'o', 'ộ'=>'o', 'ổ'=>'o', 'ỗ'=>'o', 'ơ'=>'o', 'ờ'=>'o', 'ớ'=>'o', 'ợ'=>'o', 'ở'=>'o', 'ỡ'=>'o',
            'ù'=>'u', 'ú'=>'u', 'ụ'=>'u', 'ủ'=>'u', 'ũ'=>'u', 'ư'=>'u', 'ừ'=>'u', 'ứ'=>'u', 'ự'=>'u', 'ử'=>'u', 'ữ'=>'u',
            'ỳ'=>'y', 'ý'=>'y', 'ỵ'=>'y', 'ỷ'=>'y', 'ỹ'=>'y',
            'đ'=>'d',
        ];

        return strtr($str, $accents);
    }

    /**
     * @return array<mixed>
     */
    private function parseArrayOrJson(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $trimmed = trim($data);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }
}
