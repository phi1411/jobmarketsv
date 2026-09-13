<?php

namespace JobMarket\Infrastructure\Maps;

use JobMarket\Domain\Maps\MapsProviderInterface;
use JobMarket\Exceptions\AppException;
use JobMarket\Facades\Config;

class GoongMapsClient implements MapsProviderInterface
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeoutSeconds;
    /** @var null|callable */
    private $httpGet;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null, ?int $timeoutSeconds = null, ?callable $httpGet = null)
    {
        $this->apiKey = trim((string)($apiKey ?? Config::goongRestApiKey()));
        $this->baseUrl = rtrim((string)($baseUrl ?? Config::goongApiBaseUrl()), "/");
        $this->timeoutSeconds = $timeoutSeconds ?? Config::goongTimeoutSeconds();
        $this->httpGet = $httpGet;
    }

    public function autocomplete(string $input, ?float $latitude = null, ?float $longitude = null, int $limit = 8, int $radiusKm = 50, ?string $sessionToken = null): array
    {
        $input = trim($input);
        if (strlen($input) < 2 || strlen($input) > 200) {
            throw new AppException("Từ khóa địa chỉ phải có từ 2 đến 200 ký tự.", 422);
        }

        $params = [
            "input" => $input,
            "limit" => max(1, min(10, $limit)),
            "radius" => max(1, min(100, $radiusKm)),
            "more_compound" => "true",
        ];
        if ($latitude !== null || $longitude !== null) {
            $this->assertCoordinates($latitude, $longitude);
            $params["location"] = $latitude . "," . $longitude;
        }
        if ($sessionToken !== null && trim($sessionToken) !== "") {
            $params["sessiontoken"] = $this->validateSessionToken($sessionToken);
        }

        $payload = $this->request("/Place/AutoComplete", $params);
        $predictions = is_array($payload["predictions"] ?? null) ? $payload["predictions"] : [];

        return array_values(array_map(function (array $item): array {
            $compound = is_array($item["compound"] ?? null) ? $item["compound"] : [];
            return [
                "place_id" => (string)($item["place_id"] ?? ""),
                "description" => (string)($item["description"] ?? ""),
                "matched_substrings" => $item["matched_substrings"] ?? [],
                "province" => $this->nullableString($compound["province"] ?? null),
                "commune" => $this->nullableString($compound["commune"] ?? null),
                "district_text_legacy" => $this->nullableString($compound["district"] ?? null),
            ];
        }, array_filter($predictions, "is_array")));
    }

    public function placeDetail(string $placeId, ?string $sessionToken = null): array
    {
        $placeId = trim($placeId);
        if ($placeId === "" || strlen($placeId) > 768) {
            throw new AppException("place_id không hợp lệ.", 422);
        }
        $params = ["place_id" => $placeId];
        if ($sessionToken !== null && trim($sessionToken) !== "") {
            $params["sessiontoken"] = $this->validateSessionToken($sessionToken);
        }

        $payload = $this->request("/Place/Detail", $params);
        $result = is_array($payload["result"] ?? null) ? $payload["result"] : [];
        return $this->normalizePlace($result, $placeId);
    }

    public function geocode(string $address): array
    {
        $address = trim($address);
        if (strlen($address) < 3 || strlen($address) > 500) {
            throw new AppException("Địa chỉ phải có từ 3 đến 500 ký tự.", 422);
        }
        $payload = $this->request("/Geocode", ["address" => $address]);
        return $this->normalizeGeocodeResults($payload);
    }

    public function reverseGeocode(float $latitude, float $longitude): array
    {
        $this->assertCoordinates($latitude, $longitude);
        $payload = $this->request("/Geocode", ["latlng" => $latitude . "," . $longitude]);
        return $this->normalizeGeocodeResults($payload);
    }

    public function distanceMatrix(array $origins, array $destinations, string $vehicle = "bike"): array
    {
        if ($origins === [] || $destinations === [] || count($origins) > 10 || count($destinations) > 20) {
            throw new AppException("Distance Matrix yêu cầu 1-10 điểm đi và 1-20 điểm đến.", 422);
        }
        $vehicle = strtolower(trim($vehicle));
        if (!in_array($vehicle, ["car", "bike", "taxi", "truck", "hd"], true)) {
            throw new AppException("Phương tiện không hợp lệ.", 422);
        }

        $formatPoints = function (array $points): string {
            $formatted = [];
            foreach ($points as $point) {
                if (!is_array($point) || !isset($point["latitude"], $point["longitude"])) {
                    throw new AppException("Tọa độ Distance Matrix không hợp lệ.", 422);
                }
                $lat = (float)$point["latitude"];
                $lng = (float)$point["longitude"];
                $this->assertCoordinates($lat, $lng);
                $formatted[] = $lat . "," . $lng;
            }
            return implode("|", $formatted);
        };

        $payload = $this->request("/DistanceMatrix", [
            "origins" => $formatPoints($origins),
            "destinations" => $formatPoints($destinations),
            "vehicle" => $vehicle,
        ]);

        return [
            "rows" => is_array($payload["rows"] ?? null) ? $payload["rows"] : [],
            "origin_addresses" => $payload["origin_addresses"] ?? [],
            "destination_addresses" => $payload["destination_addresses"] ?? [],
            "vehicle" => $vehicle,
        ];
    }

    private function normalizeGeocodeResults(array $payload): array
    {
        $results = is_array($payload["results"] ?? null) ? $payload["results"] : [];
        return array_values(array_map(fn(array $item): array => $this->normalizePlace($item), array_filter($results, "is_array")));
    }

    private function normalizePlace(array $item, ?string $fallbackPlaceId = null): array
    {
        $geometry = is_array($item["geometry"] ?? null) ? $item["geometry"] : [];
        $location = is_array($geometry["location"] ?? null) ? $geometry["location"] : [];
        $compound = is_array($item["compound"] ?? null) ? $item["compound"] : [];
        $components = is_array($item["address_components"] ?? null) ? $item["address_components"] : [];

        $province = $this->nullableString($compound["province"] ?? null);
        $commune = $this->nullableString($compound["commune"] ?? null);
        $district = $this->nullableString($compound["district"] ?? null);
        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }
            $types = is_array($component["types"] ?? null) ? $component["types"] : [];
            $name = $this->nullableString($component["long_name"] ?? ($component["name"] ?? null));
            if ($name === null) {
                continue;
            }
            if ($province === null && (in_array("administrative_area_level_1", $types, true) || in_array("province", $types, true))) {
                $province = $name;
            } elseif ($commune === null && (in_array("administrative_area_level_3", $types, true) || in_array("commune", $types, true) || in_array("ward", $types, true))) {
                $commune = $name;
            } elseif ($district === null && in_array("administrative_area_level_2", $types, true)) {
                $district = $name;
            }
        }

        return [
            "place_id" => (string)($item["place_id"] ?? $fallbackPlaceId ?? ""),
            "name" => $this->nullableString($item["name"] ?? null),
            "address_text" => (string)($item["formatted_address"] ?? ($item["description"] ?? ($item["name"] ?? ""))),
            "province" => $province,
            "commune" => $commune,
            "district_text_legacy" => $district,
            "latitude" => isset($location["lat"]) ? (float)$location["lat"] : null,
            "longitude" => isset($location["lng"]) ? (float)$location["lng"] : null,
            "provider" => "goong",
        ];
    }

    private function request(string $path, array $params): array
    {
        if ($this->apiKey === "") {
            throw new AppException("Chưa cấu hình GOONG_REST_API_KEY trên máy chủ.", 503);
        }
        $params["api_key"] = $this->apiKey;
        $url = $this->baseUrl . $path . "?" . http_build_query($params, "", "&", PHP_QUERY_RFC3986);

        if ($this->httpGet !== null) {
            $response = ($this->httpGet)($url, $this->timeoutSeconds);
            $status = (int)($response["status"] ?? 200);
            $body = (string)($response["body"] ?? "");
        } else {
            if (!function_exists("curl_init")) {
                throw new AppException("Máy chủ chưa bật PHP cURL để kết nối Goong.", 503);
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => min(5, $this->timeoutSeconds),
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => ["Accept: application/json"],
            ]);
            $body = curl_exec($ch);
            $errorNo = curl_errno($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $errorNo !== 0) {
                $code = $errorNo === CURLE_OPERATION_TIMEDOUT ? 504 : 503;
                throw new AppException("Không thể kết nối dịch vụ bản đồ. Vui lòng thử lại.", $code);
            }
        }

        $decoded = json_decode($body, true);
        if ($status === 429) {
            throw new AppException("Dịch vụ bản đồ đang giới hạn lượt gọi. Vui lòng thử lại sau.", 429);
        }
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            throw new AppException("Dịch vụ bản đồ tạm thời không khả dụng.", $status >= 500 ? 503 : 422);
        }
        $providerStatus = strtoupper((string)($decoded["status"] ?? "OK"));
        if (!in_array($providerStatus, ["OK", "ZERO_RESULTS"], true)) {
            throw new AppException("Goong từ chối yêu cầu địa chỉ: " . $providerStatus, 422);
        }
        return $decoded;
    }

    private function assertCoordinates(?float $latitude, ?float $longitude): void
    {
        if ($latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new AppException("Tọa độ không hợp lệ.", 422);
        }
    }

    private function validateSessionToken(string $token): string
    {
        $token = trim($token);
        if (strlen($token) > 100 || !preg_match('/^[0-9a-zA-Z\-_]+$/', $token)) {
            throw new AppException("session_token không hợp lệ.", 422);
        }
        return $token;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string)$value) : "";
        return $value === "" ? null : $value;
    }
}
