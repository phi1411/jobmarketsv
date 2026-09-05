<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\ProfileService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class ProfileController extends Controller
{
    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    public function index(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem thông tin hồ sơ.");
        }

        $profile = $this->profileService->getMyProfile($user);

        return Response::success($profile, "Thông tin hồ sơ sinh viên cá nhân.");
    }

    public function update(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để cập nhật hồ sơ.");
        }

        $profile = $this->profileService->updateMyProfile($user, $request->all());

        return Response::success($profile, "Cập nhật hồ sơ sinh viên thành công.");
    }

    public function passUpdate(Request $request): Response
    {
        return Response::success(null, "Tính năng đổi mật khẩu đang được phát triển.");
    }
}