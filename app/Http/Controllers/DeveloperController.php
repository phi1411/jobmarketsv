<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\ProfileService;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class DeveloperController extends Controller
{
    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    public function index(Request $request): Response
    {
        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->profileService->getPublicProfiles($request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách ứng viên sinh viên công khai.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    public function show(Request $request, string $id): Response
    {
        $profile = $this->profileService->getPublicProfile($id);

        return Response::success($profile, "Thông tin hồ sơ ứng viên công khai.");
    }

    public function store(Request $request): Response
    {
        return Response::error("Vui lòng sử dụng POST /register để đăng ký tài khoản mới.", Response::HTTP_BAD_REQUEST);
    }

    public function update(Request $request, string $id): Response
    {
        return Response::error("Vui lòng sử dụng PUT /student/profile để cập nhật hồ sơ của chính bạn.", Response::HTTP_BAD_REQUEST);
    }

    public function destroy(Request $request, string $id): Response
    {
        return Response::error("Không thể xóa tài khoản từ endpoint này.", Response::HTTP_BAD_REQUEST);
    }
}