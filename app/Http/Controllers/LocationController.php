<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\LocationRepository;

class LocationController extends Controller
{
    private LocationRepository $locationRepo;

    public function __construct()
    {
        $this->locationRepo = new LocationRepository();
    }

    public function index(Request $request): Response
    {
        $locations = $this->locationRepo->getAll();
        return Response::success($locations, "Danh sách địa điểm.");
    }

    public function hierarchy(Request $request): Response
    {
        return Response::success(
            $this->locationRepo->getHierarchy(),
            "Danh sách địa điểm theo tỉnh/thành và khu vực."
        );
    }

    public function administrative(Request $request): Response
    {
        $schema = strtolower(trim((string)$request->get("schema", "current")));
        if (!in_array($schema, ["current", "legacy"], true)) {
            return Response::error("Kiểu dữ liệu địa chỉ không hợp lệ.", 422);
        }

        return Response::success(
            $this->locationRepo->getAdministrativeHierarchy($schema),
            $schema === "legacy"
                ? "Danh sách Tỉnh/Thành phố, Quận/Huyện và Phường/Xã theo địa chỉ cũ."
                : "Danh sách toàn quốc gồm Tỉnh/Thành phố và Phường/Xã hiện hành."
        );
    }

    public function show(Request $request, string $id)
    {
    }

    public function store(Request $request)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(Request $request, string $id)
    {
    }
}
