<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Maps\MapsProviderInterface;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Infrastructure\CompanyRepository;
use JobMarket\Infrastructure\JobLocationRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Infrastructure\Maps\GoongMapsClient;
use JobMarket\Support\Pagination;

class LocationFeatureService
{
    public function __construct(
        private ?MapsProviderInterface $maps = null,
        private ?JobLocationRepository $locations = null,
        private ?JobRepository $jobs = null,
        private ?CompanyRepository $companies = null
    ) {
        $this->maps ??= new GoongMapsClient();
        $this->locations ??= new JobLocationRepository();
        $this->jobs ??= new JobRepository();
        $this->companies ??= new CompanyRepository();
    }

    public function autocomplete(array $data): array
    {
        [$lat, $lng] = $this->optionalCoordinates($data);
        return $this->maps->autocomplete(
            (string)($data["input"] ?? ""),
            $lat,
            $lng,
            (int)($data["limit"] ?? 8),
            (int)($data["radius_km"] ?? 50),
            isset($data["session_token"]) ? (string)$data["session_token"] : null
        );
    }

    public function placeDetail(array $data): array
    {
        return $this->maps->placeDetail(
            (string)($data["place_id"] ?? ""),
            isset($data["session_token"]) ? (string)$data["session_token"] : null
        );
    }

    public function geocode(array $data): array
    {
        return $this->maps->geocode((string)($data["address"] ?? ""));
    }

    public function reverseGeocode(array $data): array
    {
        [$lat, $lng] = $this->requiredCoordinates($data);
        return $this->maps->reverseGeocode($lat, $lng);
    }

    public function getJobLocations(string $jobId, bool $publicOnly = true): array
    {
        $job = $this->jobs->findById($jobId);
        if ($job === [] || ($publicOnly && !$this->isPublicJob($job))) {
            throw new NotFoundException("Không tìm thấy tin tuyển dụng.");
        }
        return $this->locations->listForJob($jobId);
    }

    public function addJobLocation(string $jobId, array $data, array $user): array
    {
        $this->assertJobOwner($jobId, $user);
        if (count($this->locations->listForJob($jobId)) >= 20) {
            throw new ValidationException(["work_locations" => ["Mỗi tin được có tối đa 20 địa điểm làm việc."]]);
        }
        return $this->locations->create($jobId, $this->resolveLocation($data));
    }

    public function updateJobLocation(string $jobId, string $locationId, array $data, array $user): array
    {
        $this->assertJobOwner($jobId, $user);
        $current = $this->locations->find($jobId, $locationId);
        if ($current === null) {
            throw new NotFoundException("Không tìm thấy địa điểm làm việc.");
        }
        $resolved = (isset($data["place_id"]) || isset($data["address_text"]) || isset($data["latitude"]))
            ? $this->resolveLocation(array_merge($current, $data))
            : $current;
        $resolved["branch_name"] = $this->shortText($data["branch_name"] ?? $current["branch_name"], 150);
        $resolved["is_primary"] = array_key_exists("is_primary", $data) ? (bool)$data["is_primary"] : (bool)$current["is_primary"];
        return $this->locations->update($jobId, $locationId, $resolved);
    }

    public function deleteJobLocation(string $jobId, string $locationId, array $user): void
    {
        $this->assertJobOwner($jobId, $user);
        if ($this->locations->find($jobId, $locationId) === null) {
            throw new NotFoundException("Không tìm thấy địa điểm làm việc.");
        }
        $this->locations->delete($jobId, $locationId);
    }

    public function nearby(array $data, Pagination $pagination): array
    {
        [$lat, $lng] = $this->requiredCoordinates($data);
        $radius = (int)($data["radius_km"] ?? 10);
        if (!in_array($radius, [2, 5, 10, 20, 50], true)) {
            throw new ValidationException(["radius_km" => ["Bán kính cho phép: 2, 5, 10, 20 hoặc 50 km."]]);
        }
        foreach (["work_mode", "work_type", "category_id"] as $field) {
            if (isset($data[$field]) && !is_scalar($data[$field])) {
                throw new ValidationException([$field => ["Bộ lọc không hợp lệ."]]);
            }
        }
        $result = $this->locations->nearby($lat, $lng, $radius, $data, $pagination);
        $result["origin"] = ["latitude" => $lat, "longitude" => $lng];
        $result["radius_km"] = $radius;
        $result["distance_type"] = "straight_line";
        return $result;
    }

    public function commuteCheck(string $jobId, array $data): array
    {
        $job = $this->jobs->findById($jobId);
        if ($job === [] || !$this->isPublicJob($job)) {
            throw new NotFoundException("Không tìm thấy tin tuyển dụng.");
        }
        [$lat, $lng] = $this->requiredCoordinates($data);
        $maxCommute = (int)($data["max_commute_km"] ?? 15);
        if ($maxCommute < 1 || $maxCommute > 100) {
            throw new ValidationException(["max_commute_km" => ["Quãng đường mong muốn phải từ 1 đến 100 km."]]);
        }
        $locations = array_values(array_filter(
            $this->locations->listForJob($jobId),
            fn(array $item): bool => $item["latitude"] !== null && $item["longitude"] !== null
        ));
        if ($locations === []) {
            throw new ValidationException(["work_locations" => ["Tin này chưa có địa điểm đã chuẩn hóa tọa độ."]]);
        }

        $nearest = null;
        foreach ($locations as $location) {
            $distance = JobLocationRepository::haversine($lat, $lng, (float)$location["latitude"], (float)$location["longitude"]);
            if ($nearest === null || $distance < $nearest["distance_km"]) {
                $nearest = ["distance_km" => $distance, "location" => $location];
            }
        }
        $straightKm = round((float)$nearest["distance_km"], 2);
        $result = [
            "job_id" => $jobId,
            "nearest_location" => $nearest["location"],
            "straight_line_distance_km" => $straightKm,
            "route_distance_km" => null,
            "route_duration_seconds" => null,
            "vehicle" => null,
        ];

        if (filter_var($data["use_route"] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $vehicle = strtolower((string)($data["vehicle"] ?? "bike"));
            $matrix = $this->maps->distanceMatrix(
                [["latitude" => $lat, "longitude" => $lng]],
                array_map(fn(array $item): array => ["latitude" => $item["latitude"], "longitude" => $item["longitude"]], $locations),
                $vehicle
            );
            $elements = $matrix["rows"][0]["elements"] ?? [];
            $bestIndex = null;
            $bestMeters = PHP_INT_MAX;
            foreach ($elements as $index => $element) {
                $meters = $element["distance"]["value"] ?? null;
                if (is_numeric($meters) && (int)$meters < $bestMeters) {
                    $bestMeters = (int)$meters;
                    $bestIndex = $index;
                }
            }
            if ($bestIndex !== null) {
                $result["nearest_location"] = $locations[$bestIndex];
                $result["route_distance_km"] = round($bestMeters / 1000, 2);
                $result["route_duration_seconds"] = isset($elements[$bestIndex]["duration"]["value"])
                    ? (int)$elements[$bestIndex]["duration"]["value"] : null;
                $result["vehicle"] = $vehicle;
            }
        }

        $comparedDistance = $result["route_distance_km"] ?? $straightKm;
        $result["max_commute_km"] = $maxCommute;
        $result["is_far"] = $comparedDistance > $maxCommute;
        $result["warning"] = $result["is_far"]
            ? "Công việc này cách khu vực của bạn khoảng {$comparedDistance} km, vượt mức {$maxCommute} km mong muốn. Bạn vẫn có thể ứng tuyển."
            : null;
        return $result;
    }

    public function studentPreferences(array $user): array
    {
        $this->assertStudent($user);
        return $this->locations->studentPreferences((string)$user["id"]);
    }

    public function saveStudentPreferences(array $data, array $user): array
    {
        $this->assertStudent($user);
        $items = $data["locations"] ?? null;
        if (!is_array($items) || count($items) > 10) {
            throw new ValidationException(["locations" => ["Hãy gửi một mảng tối đa 10 khu vực mong muốn."]]);
        }
        $resolved = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new ValidationException(["locations.{$index}" => ["Khu vực không hợp lệ."]]);
            }
            $location = $this->resolveLocation($item);
            $radius = (int)($item["preferred_radius_km"] ?? 10);
            if (!in_array($radius, [2, 5, 10, 20, 50], true)) {
                throw new ValidationException(["locations.{$index}.preferred_radius_km" => ["Bán kính cho phép: 2, 5, 10, 20 hoặc 50 km."]]);
            }
            $location["preferred_radius_km"] = $radius;
            $resolved[] = $location;
        }
        return $this->locations->replaceStudentPreferences((string)$user["id"], $resolved);
    }

    private function resolveLocation(array $data): array
    {
        $placeId = trim((string)($data["place_id"] ?? ($data["provider_place_id"] ?? "")));
        if ($placeId !== "" && (($data["provider"] ?? "goong") === "goong" || isset($data["place_id"]))) {
            $location = $this->maps->placeDetail($placeId, isset($data["session_token"]) ? (string)$data["session_token"] : null);
            if ($location["latitude"] === null || $location["longitude"] === null) {
                throw new ValidationException(["place_id" => ["Goong không trả về tọa độ cho địa điểm này."]]);
            }
            $location["provider_place_id"] = $location["place_id"];
            unset($location["place_id"], $location["name"]);
            $location["geocode_status"] = "verified";
        } else {
            [$lat, $lng] = $this->requiredCoordinates($data);
            $address = trim((string)($data["address_text"] ?? ""));
            if (strlen($address) < 3 || strlen($address) > 500) {
                throw new ValidationException(["address_text" => ["Địa chỉ phải có từ 3 đến 500 ký tự."]]);
            }
            $location = [
                "address_text" => $address,
                "province" => $this->shortText($data["province"] ?? null, 150),
                "province_code" => $this->shortText($data["province_code"] ?? null, 30),
                "commune" => $this->shortText($data["commune"] ?? null, 150),
                "commune_code" => $this->shortText($data["commune_code"] ?? null, 30),
                "district_text_legacy" => $this->shortText($data["district_text_legacy"] ?? null, 150),
                "latitude" => $lat,
                "longitude" => $lng,
                "provider" => "manual",
                "provider_place_id" => null,
                "geocode_status" => "manual",
            ];
        }
        $location["branch_name"] = $this->shortText($data["branch_name"] ?? null, 150);
        $location["is_primary"] = (bool)($data["is_primary"] ?? false);
        return $location;
    }

    private function assertJobOwner(string $jobId, array $user): array
    {
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ công ty sở hữu tin được sửa địa điểm làm việc.");
        }
        $job = $this->jobs->findById($jobId);
        $company = $this->companies->findByUserId((string)($user["id"] ?? ""));
        if ($job === [] || $company === null || $job["company_id"] !== $company["id"]) {
            throw new AuthorizationException("Bạn không có quyền sửa địa điểm của tin này.");
        }
        return $job;
    }

    private function assertStudent(array $user): void
    {
        if (!in_array(($user["role"] ?? ""), ["student", "developer"], true)) {
            throw new AuthorizationException("Tính năng này chỉ dành cho sinh viên.");
        }
    }

    private function requiredCoordinates(array $data): array
    {
        if (!is_numeric($data["latitude"] ?? null) || !is_numeric($data["longitude"] ?? null)) {
            throw new ValidationException(["coordinates" => ["latitude và longitude là bắt buộc."]]);
        }
        $lat = (float)$data["latitude"];
        $lng = (float)$data["longitude"];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new ValidationException(["coordinates" => ["Tọa độ không hợp lệ."]]);
        }
        return [$lat, $lng];
    }

    private function optionalCoordinates(array $data): array
    {
        if (($data["latitude"] ?? "") === "" && ($data["longitude"] ?? "") === "") {
            return [null, null];
        }
        return $this->requiredCoordinates($data);
    }

    private function shortText(mixed $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        if ($value === "") {
            return null;
        }
        if (strlen($value) > $max) {
            throw new ValidationException(["text" => ["Dữ liệu địa chỉ vượt quá {$max} ký tự."]]);
        }
        return $value;
    }

    private function isPublicJob(array $job): bool
    {
        if (($job["status"] ?? "") !== "published") {
            return false;
        }
        $deadline = $job["application_deadline"] ?? ($job["deadline"] ?? null);
        return empty($deadline) || strtotime((string)$deadline) >= strtotime(date("Y-m-d"));
    }
}
