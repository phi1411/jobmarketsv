<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\LocationFeatureService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class JobLocationController extends Controller
{
    private LocationFeatureService $service;

    public function __construct()
    {
        $this->service = new LocationFeatureService();
    }

    public function index(Request $request, string $id): Response
    {
        return Response::success($this->service->getJobLocations($id), "Các địa điểm làm việc của tin.");
    }

    public function store(Request $request, string $id): Response
    {
        $user = $this->requireUser($request);
        return Response::created($this->service->addJobLocation($id, $request->all(), $user), "Đã thêm địa điểm làm việc.");
    }

    public function update(Request $request, string $jobId, string $locationId): Response
    {
        $user = $this->requireUser($request);
        return Response::success(
            $this->service->updateJobLocation($jobId, $locationId, $request->all(), $user),
            "Đã cập nhật địa điểm làm việc."
        );
    }

    public function destroy(Request $request, string $jobId, string $locationId): Response
    {
        $user = $this->requireUser($request);
        $this->service->deleteJobLocation($jobId, $locationId, $user);
        return Response::success(null, "Đã xóa địa điểm làm việc.");
    }

    public function nearby(Request $request): Response
    {
        $pagination = Pagination::fromParams($request->all(), 0, 12);
        $result = $this->service->nearby($request->all(), $pagination);
        return Response::success(
            $result["items"],
            "Việc làm gần vị trí của bạn.",
            Response::HTTP_OK,
            array_merge($pagination->toMeta($result["total"]), [
                "origin" => $result["origin"],
                "radius_km" => $result["radius_km"],
                "distance_type" => $result["distance_type"],
            ])
        );
    }

    public function commuteCheck(Request $request, string $id): Response
    {
        return Response::success($this->service->commuteCheck($id, $request->all()), "Đã kiểm tra khoảng cách đi làm.");
    }

    public function preferences(Request $request): Response
    {
        return Response::success(
            $this->service->studentPreferences($this->requireUser($request)),
            "Khu vực làm việc mong muốn."
        );
    }

    public function savePreferences(Request $request): Response
    {
        return Response::success(
            $this->service->saveStudentPreferences($request->all(), $this->requireUser($request)),
            "Đã lưu khu vực làm việc mong muốn."
        );
    }

    private function requireUser(Request $request): array
    {
        $user = $request->getUser();
        if ($user === null) {
            throw new AuthenticationException("Vui lòng đăng nhập để sử dụng tính năng này.");
        }
        return $user;
    }
}
