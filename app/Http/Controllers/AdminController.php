<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\AdminService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class AdminController extends Controller
{
    private AdminService $adminService;

    public function __construct()
    {
        $this->adminService = new AdminService();
    }

    private function getAuthenticatedAdmin(Request $request): array
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập với quyền Quản trị viên.");
        }
        return $user;
    }

    /**
     * Get list of users (GET /admin/users)
     */
    public function users(Request $request): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->adminService->listUsers($admin, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách người dùng hệ thống.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Get detail of a specific user (GET /admin/users/{id})
     */
    public function showUser(Request $request, string $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $user = $this->adminService->getUserDetail($admin, $id);

        return Response::success($user, "Chi tiết thông tin người dùng.");
    }

    /**
     * Update user status (active, suspended, banned) (PATCH /admin/users/{id}/status)
     */
    public function updateUserStatus(Request $request, string $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $updatedUser = $this->adminService->updateUserStatus($admin, $id, $request->all());

        return Response::success($updatedUser, "Cập nhật trạng thái người dùng thành công.");
    }

    /**
     * Get list of companies (GET /admin/companies)
     */
    public function companies(Request $request): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->adminService->listCompanies($admin, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách công ty / nhà tuyển dụng.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Update company verification status (PATCH /admin/companies/{id}/verification)
     */
    public function verifyCompany(Request $request, string $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $updatedCompany = $this->adminService->verifyCompany($admin, $id, $request->all());

        return Response::success($updatedCompany, "Cập nhật trạng thái xác thực công ty thành công.");
    }

    /**
     * Get all jobs on marketplace (GET /admin/jobs)
     */
    public function jobs(Request $request): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->adminService->listJobs($admin, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách tin tuyển dụng toàn hệ thống.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Moderate a job posting (PATCH /admin/jobs/{id}/moderation)
     */
    public function moderateJob(Request $request, string $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $updatedJob = $this->adminService->moderateJob($admin, $id, $request->all());

        return Response::success($updatedJob, "Kiểm duyệt tin tuyển dụng thành công.");
    }

    /**
     * Get audit logs (GET /admin/audit-logs)
     */
    public function auditLogs(Request $request): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->adminService->listAuditLogs($admin, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Nhật ký thao tác quản trị hệ thống.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }
}
