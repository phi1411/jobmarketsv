<?php

namespace JobMarket\Domain\Maps;

interface MapsProviderInterface
{
    public function autocomplete(string $input, ?float $latitude = null, ?float $longitude = null, int $limit = 8, int $radiusKm = 50, ?string $sessionToken = null): array;

    public function placeDetail(string $placeId, ?string $sessionToken = null): array;

    public function geocode(string $address): array;

    public function reverseGeocode(float $latitude, float $longitude): array;

    public function distanceMatrix(array $origins, array $destinations, string $vehicle = "bike"): array;
}
