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
