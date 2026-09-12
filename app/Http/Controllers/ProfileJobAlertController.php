<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\ProfileJobAlertService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class ProfileJobAlertController extends Controller
{
    public function __construct(private ?ProfileJobAlertService $service = null)
    {
        $this->service ??= new ProfileJobAlertService();
    }

    public function show(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem cài đặt thông báo.");
        }
        return Response::success($this->service->getSettings($user), "Cài đặt thông báo việc làm cá nhân hóa.");
    }

    public function update(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để cập nhật thông báo.");
        }
        return Response::success(
            $this->service->updateSettings($user, $request->all()),
            "Đã cập nhật thông báo việc làm cá nhân hóa."
        );
    }
}
