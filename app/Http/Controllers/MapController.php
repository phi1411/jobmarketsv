<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\LocationFeatureService;
use JobMarket\Facades\Config;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\Security\FileRateLimiter;

class MapController extends Controller
{
    private LocationFeatureService $service;
    private FileRateLimiter $rateLimiter;

    public function __construct()
    {
        $this->service = new LocationFeatureService();
        $this->rateLimiter = new FileRateLimiter();
    }

    public function autocomplete(Request $request): Response
    {
        if (($limited = $this->limit($request, "autocomplete")) !== null) {
            return $limited;
        }
        return Response::success($this->service->autocomplete($request->all()), "Gợi ý địa chỉ từ Goong.");
    }

    public function detail(Request $request): Response
    {
        if (($limited = $this->limit($request, "detail")) !== null) {
            return $limited;
        }
        return Response::success($this->service->placeDetail($request->all()), "Chi tiết địa điểm từ Goong.");
    }

    public function geocode(Request $request): Response
    {
        if (($limited = $this->limit($request, "geocode")) !== null) {
            return $limited;
        }
        return Response::success($this->service->geocode($request->all()), "Kết quả chuẩn hóa địa chỉ.");
    }

    public function reverseGeocode(Request $request): Response
    {
        if (($limited = $this->limit($request, "reverse")) !== null) {
            return $limited;
        }
        return Response::success($this->service->reverseGeocode($request->all()), "Địa chỉ tại vị trí hiện tại.");
    }

    private function limit(Request $request, string $action): ?Response
    {
        $user = $request->getUser();
        $identity = !empty($user["id"])
            ? "user:" . $user["id"]
            : "ip:" . hash("sha256", $request->getClientIp());
        $result = $this->rateLimiter->consume(
            "location:{$action}:{$identity}",
            Config::locationApiRateLimit(),
            60
        );
        if ($result->allowed) {
            return null;
        }
        if ($result->failedClosed) {
            return Response::error("Hệ thống kiểm soát tần suất tạm thời không khả dụng.", Response::HTTP_SERVICE_UNAVAILABLE);
        }
        return Response::error(
            "Bạn thao tác địa chỉ quá nhanh. Vui lòng thử lại sau.",
            Response::HTTP_TOO_MANY_REQUESTS,
            ["retry_after_seconds" => max(1, $result->retryAfter)],
            ["Retry-After" => (string)max(1, $result->retryAfter)]
        );
    }
}
