<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services\Normalizers;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\ValueObjects\CandidateLocationValue;
use JobMarket\Domain\Matching\ValueObjects\JobLocationValue;

final class LocationNormalizer
{
    /**
     * @param array<string, string> $locationDictionary id => canonical_name map
     */
    public function __construct(
        private array $locationDictionary = []
    ) {
    }

    /**
     * Normalizes candidate locations from student profile.
     * Uses location_id, preferred_location, and preferred_locations.
     * Strictly omits exact street address!
     */
    public function normalizeCandidateLocations(
        ?string $locationId,
        ?string $preferredLocation = null,
        mixed $preferredLocations = null
    ): CandidateLocationValue {
        $locationIds = [];
        $names = [];

        // Primary location ID
        if (!empty($locationId)) {
            $lid = trim((string)$locationId);
            if (isset($this->locationDictionary[$lid])) {
                $locationIds[] = $lid;
                $names[] = $this->locationDictionary[$lid];
            }
            // If ID is not in dictionary, do not assume exact match
        }

        // Additional preferred locations (can be array or JSON)
        $extraIds = $this->parseArrayOrJson($preferredLocations);
        foreach ($extraIds as $rawEid) {
            $eid = trim((string)$rawEid);
            if ($eid !== '' && !in_array($eid, $locationIds, true)) {
                if (isset($this->locationDictionary[$eid])) {
                    $locationIds[] = $eid;
                    $names[] = $this->locationDictionary[$eid];
                }
            }
        }

        // Preferred location text (e.g. "Hà Nội - Cầu Giấy")
        if (!empty($preferredLocation)) {
            $pLoc = trim((string)$preferredLocation);
            if (!\JobMarket\Domain\Matching\Services\PiiRedactor::isPlaceholderOrContact($pLoc)) {
                $cleanLoc = \JobMarket\Domain\Matching\Services\PiiRedactor::stripPlaceholdersAndContact($pLoc);
                if ($cleanLoc !== '' && !\JobMarket\Domain\Matching\Services\PiiRedactor::containsPlaceholder($cleanLoc) && !in_array($cleanLoc, $names, true) && mb_strlen($cleanLoc) <= 255) {
                    $names[] = $cleanLoc;
                }
            }
        }

        $state = (!empty($locationIds) || !empty($names)) ? DataState::AVAILABLE : DataState::UNKNOWN;

        return new CandidateLocationValue(
            state: $state,
            locationIds: array_values(array_unique($locationIds)),
            names: array_values(array_unique($names))
        );
    }

    /**
     * Normalizes job location from job record.
     * Uses location_id, city, district, work_mode.
     * Strictly omits exact street address (`address`)!
     */
    public function normalizeJobLocation(
        ?string $locationId,
        ?string $city,
        ?string $district,
        ?string $workMode = 'onsite'
    ): JobLocationValue {
        $cleanLocationId = null;
        if (!empty($locationId)) {
            $lid = trim((string)$locationId);
            if (isset($this->locationDictionary[$lid])) {
                $cleanLocationId = $lid;
            }
        }

        $cleanCity = !empty($city) ? trim((string)$city) : null;
        $cleanDistrict = !empty($district) ? trim((string)$district) : null;
        $cleanWorkMode = !empty($workMode) ? trim((string)$workMode) : 'onsite';

        $hasLocationInfo = ($cleanLocationId !== null || $cleanCity !== null || $cleanDistrict !== null || $cleanWorkMode === 'remote');
        $state = $hasLocationInfo ? DataState::AVAILABLE : DataState::UNKNOWN;

        return new JobLocationValue(
            state: $state,
            locationId: $cleanLocationId,
            city: $cleanCity,
            district: $cleanDistrict,
            workMode: $cleanWorkMode
        );
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
