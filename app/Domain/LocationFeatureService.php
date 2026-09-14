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
    private static array $administrativeData = [];

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

    /**
     * Normalize either a browser GPS point or a structured manual address.
     * The same contract is shared by student search/preferences and company job locations.
     */
    public function resolveInputLocation(array $data): array
    {
        $location = $this->resolveLocation($data);
        unset($location["branch_name"], $location["is_primary"]);
        return $location;
    }

    public function getJobLocations(string $jobId, ?array $user = null): array
    {
        $job = $this->jobs->findById($jobId);
        if ($job === []) {
            throw new NotFoundException("Không tìm thấy tin tuyển dụng.");
        }
        if (!$this->isPublicJob($job)) {
            if (($user["role"] ?? "") !== "admin") {
                if ($user === null) {
                    throw new NotFoundException("Không tìm thấy tin tuyển dụng.");
                }
                $this->assertJobOwner($jobId, $user);
            }
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
        foreach (["work_mode", "work_type", "category_id", "shift_type", "location_id", "city", "keyword", "salary_min", "salary_max"] as $field) {
            if (isset($data[$field]) && !is_scalar($data[$field])) {
                throw new ValidationException([$field => ["Bộ lọc không hợp lệ."]]);
            }
        }
        if (isset($data["location_ids"]) && !is_string($data["location_ids"]) && !is_array($data["location_ids"])) {
            throw new ValidationException(["location_ids" => ["Danh sách khu vực không hợp lệ."]]);
        }
        if (is_array($data["location_ids"] ?? null) && count($data["location_ids"]) > 20) {
            throw new ValidationException(["location_ids" => ["Chỉ được chọn tối đa 20 khu vực."]]);
        }
        if (!empty($data["shift_type"]) && !in_array((string)$data["shift_type"], ["morning", "afternoon", "evening", "night", "rotating", "weekend", "flexible"], true)) {
            throw new ValidationException(["shift_type" => ["Ca làm việc không hợp lệ."]]);
        }
        if (!empty($data["work_mode"]) && !in_array((string)$data["work_mode"], ["onsite", "hybrid", "remote"], true)) {
            throw new ValidationException(["work_mode" => ["Hình thức làm việc không hợp lệ."]]);
        }
        if (isset($data["keyword"]) && strlen(trim((string)$data["keyword"])) > 150) {
            throw new ValidationException(["keyword" => ["Từ khóa tìm kiếm tối đa 150 ký tự."]]);
        }
        if (isset($data["city"]) && strlen(trim((string)$data["city"])) > 150) {
            throw new ValidationException(["city" => ["Tỉnh/Thành phố tối đa 150 ký tự."]]);
        }
        foreach (["salary_min", "salary_max"] as $salaryField) {
            if (($data[$salaryField] ?? "") !== "" && (!is_numeric($data[$salaryField]) || (float)$data[$salaryField] < 0)) {
                throw new ValidationException([$salaryField => ["Mức lương phải là số không âm."]]);
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
        $source = strtolower(trim((string)($data["source"] ?? "")));
        $placeId = trim((string)($data["place_id"] ?? ($data["provider_place_id"] ?? "")));
        if ($source !== "" && !in_array($source, ["gps", "manual"], true)) {
            throw new ValidationException(["source" => ["Nguồn địa chỉ phải là gps hoặc manual."]]);
        }

        if ($source === "gps") {
            [$lat, $lng] = $this->requiredCoordinates($data);
            $results = $this->maps->reverseGeocode($lat, $lng);
            $location = $this->firstResolvedMapLocation($results, "Không xác định được địa chỉ tại vị trí GPS này.");
            // Keep the browser coordinates as the exact search origin; the returned address is descriptive.
            $location["latitude"] = $lat;
            $location["longitude"] = $lng;
            $location["provider_place_id"] = $location["place_id"] ?: null;
            unset($location["place_id"], $location["name"]);
            $location["geocode_status"] = "verified";
        } elseif ($placeId !== "" && (($data["provider"] ?? "goong") === "goong" || isset($data["place_id"]))) {
            $location = $this->maps->placeDetail($placeId, isset($data["session_token"]) ? (string)$data["session_token"] : null);
            if ($location["latitude"] === null || $location["longitude"] === null) {
                throw new ValidationException(["place_id" => ["Goong không trả về tọa độ cho địa điểm này."]]);
            }
            $location["provider_place_id"] = $location["place_id"];
            unset($location["place_id"], $location["name"]);
            $location["geocode_status"] = "verified";
        } elseif ($source === "manual" && (!is_numeric($data["latitude"] ?? null) || !is_numeric($data["longitude"] ?? null))) {
            $administrativeMode = strtolower(trim((string)($data["administrative_mode"] ?? "current")));
            if (!in_array($administrativeMode, ["current", "legacy"], true)) {
                throw new ValidationException(["administrative_mode" => ["Kiểu địa chỉ phải là current hoặc legacy."]]);
            }

            $province = $this->shortText($data["province"] ?? null, 150);
            $district = $this->shortText($data["district_text_legacy"] ?? ($data["district"] ?? null), 150);
            $commune = $this->shortText($data["commune"] ?? ($data["ward"] ?? null), 150);
            $addressDetail = $this->shortText($data["address_detail"] ?? null, 250);

            $errors = [];
            if ($province === null) {
                $errors["province"][] = "Vui lòng chọn Tỉnh/Thành phố.";
            }
            if ($administrativeMode === "legacy" && $district === null) {
                $errors["district_text_legacy"][] = "Vui lòng chọn Quận/Huyện của địa chỉ cũ.";
            }
            if ($commune === null) {
                $errors["commune"][] = "Vui lòng chọn Phường/Xã.";
            }
            if ($addressDetail === null || strlen($addressDetail) < 3) {
                $errors["address_detail"][] = "Vui lòng nhập số nhà, tên đường hoặc địa chỉ chi tiết.";
            }
            if ($errors !== []) {
                throw new ValidationException($errors);
            }

            [$province, $district, $commune, $provinceCode, $communeCode] = $this->canonicalAdministrativeSelection(
                $administrativeMode,
                $province,
                $district,
                $commune,
                $this->shortText($data["province_code"] ?? null, 30),
                $this->shortText($data["district_code"] ?? null, 30),
                $this->shortText($data["commune_code"] ?? ($data["ward_code"] ?? null), 30)
            );

            $query = implode(", ", array_filter([$addressDetail, $commune, $district, $province]));
            $results = $this->maps->geocode($query);
            $location = $this->firstResolvedMapLocation($results, "Goong không tìm thấy tọa độ phù hợp với địa chỉ đã nhập.");
            // Display the exact structured address the user selected. Providers may
            // still return pre-reform ward names even when coordinates are correct.
            $location["address_text"] = $query;
            $location["province"] = $province;
            $location["province_code"] = $provinceCode;
            $location["commune"] = $commune;
            $location["commune_code"] = $communeCode;
            $location["district_text_legacy"] = $administrativeMode === "legacy" ? $district : null;
            $location["provider_place_id"] = $location["place_id"] ?: null;
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

    /**
     * Verify the submitted hierarchy against our server-side catalogue and return
     * canonical names. This prevents a client from pairing a ward with another province.
     */
    private function canonicalAdministrativeSelection(
        string $mode,
        string $provinceName,
        ?string $districtName,
        string $communeName,
        ?string $provinceCode,
        ?string $districtCode,
        ?string $communeCode
    ): array {
        $data = $this->administrativeData($mode);
        $province = $this->findAdministrativeUnit($data["provinces"], $provinceName, $provinceCode);
        if ($province === null) {
            throw new ValidationException(["province" => ["Tỉnh/Thành phố không có trong danh mục hành chính."]]);
        }

        if ($mode === "legacy") {
            $district = $this->findAdministrativeUnit($province["districts"] ?? [], (string)$districtName, $districtCode);
            if ($district === null) {
                throw new ValidationException(["district_text_legacy" => ["Quận/Huyện không thuộc Tỉnh/Thành phố đã chọn."]]);
            }
            $commune = $this->findAdministrativeUnit($district["wards"] ?? [], $communeName, $communeCode);
            if ($commune === null) {
                throw new ValidationException(["commune" => ["Phường/Xã không thuộc Quận/Huyện đã chọn."]]);
            }
            return [
                (string)$province["name"],
                (string)$district["name"],
                (string)$commune["name"],
                (string)$province["code"],
                (string)$commune["code"],
            ];
        }

        $commune = $this->findAdministrativeUnit($province["communes"] ?? [], $communeName, $communeCode);
        if ($commune === null) {
            throw new ValidationException(["commune" => ["Phường/Xã không thuộc Tỉnh/Thành phố đã chọn."]]);
        }
        return [
            (string)$province["name"],
            null,
            (string)$commune["name"],
            (string)$province["code"],
            (string)$commune["code"],
        ];
    }

    private function administrativeData(string $mode): array
    {
        if (isset(self::$administrativeData[$mode])) {
            return self::$administrativeData[$mode];
        }
        $path = BASE_PATH . "/app/Data/vietnam_administrative_{$mode}.json";
        if (!is_file($path)) {
            throw new \RuntimeException("Dữ liệu đơn vị hành chính chưa được cài đặt.");
        }
        $decoded = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !is_array($decoded["provinces"] ?? null)) {
            throw new \RuntimeException("Dữ liệu đơn vị hành chính không hợp lệ.");
        }
        return self::$administrativeData[$mode] = $decoded;
    }

    private function findAdministrativeUnit(array $units, string $name, ?string $code): ?array
    {
        if ($code !== null) {
            foreach ($units as $unit) {
                if ((string)($unit["code"] ?? "") === $code) {
                    return $unit;
                }
            }
            return null;
        }

        $wanted = $this->normalizeAdministrativeName($name);
        foreach ($units as $unit) {
            if ($wanted !== "" && $this->normalizeAdministrativeName((string)($unit["name"] ?? "")) === $wanted) {
                return $unit;
            }
        }
        return null;
    }

    private function normalizeAdministrativeName(string $name): string
    {
        $name = trim($name);
        $name = (string)preg_replace('/^(Tỉnh|Thành phố|TP\.?|Quận|Huyện|Thị xã|Phường|Xã|Thị trấn|Đặc khu)\s+/ui', '', $name);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        return strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '', $ascii !== false ? $ascii : $name));
    }

    private function firstResolvedMapLocation(array $results, string $message): array
    {
        foreach ($results as $result) {
            if (is_array($result) && is_numeric($result["latitude"] ?? null) && is_numeric($result["longitude"] ?? null)) {
                return $result;
            }
        }
        throw new ValidationException(["address" => [$message]]);
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
