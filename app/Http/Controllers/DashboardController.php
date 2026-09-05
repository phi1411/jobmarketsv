<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\DashboardService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    /**
     * Get Student Dashboard (GET /student/dashboard)
     */
    public function studentDashboard(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem thông tin Dashboard.");
        }

        $data = $this->dashboardService->studentDashboard($user);

        return Response::success(
            $data,
            "Thông tin tổng quan dành cho sinh viên.",
            Response::HTTP_OK
        );
    }

    /**
     * Get Company Dashboard (GET /company/dashboard)
     */
    public function companyDashboard(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem thông tin Dashboard.");
        }

        $data = $this->dashboardService->companyDashboard($user);

        return Response::success(
            $data,
            "Thông tin tổng quan dành cho nhà tuyển dụng.",
            Response::HTTP_OK
        );
    }

    /**
     * Get Admin Dashboard (GET /admin/dashboard)
     */
    public function adminDashboard(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem thông tin Dashboard.");
        }

        $data = $this->dashboardService->adminDashboard($user);

        return Response::success(
            $data,
            "Thông tin tổng quan quản trị hệ thống.",
            Response::HTTP_OK
        );
    }
}
