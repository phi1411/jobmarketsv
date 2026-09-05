<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\ApplicationService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class ApplicationController extends Controller
{
    private ApplicationService $applicationService;

    public function __construct()
    {
        $this->applicationService = new ApplicationService();
    }

    /**
     * Student applies for a job (POST /jobs/{id}/applications)
     */
    public function store(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để nộp đơn ứng tuyển.");
        }

        $application = $this->applicationService->apply($user, $id, $request->all());

        return Response::success(
            $application,
            "Nộp đơn ứng tuyển thành công.",
            Response::HTTP_CREATED
        );
    }

    /**
     * Company views applications for a specific job (GET /jobs/{id}/applications)
     */
    public function index(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem danh sách ứng tuyển.");
        }

        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->applicationService->getJobApplications($user, $id, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách đơn ứng tuyển của công việc.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Student views their own submitted applications (GET /student/applications & GET /applications/me)
     */
    public function myApplications(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem danh sách đơn ứng tuyển.");
        }

        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->applicationService->getMyApplications($user, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách đơn ứng tuyển cá nhân của bạn.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Company views all applications across company jobs (GET /company/applications)
     */
    public function companyApplications(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem danh sách ứng tuyển.");
        }

        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->applicationService->getCompanyApplications($user, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách tất cả đơn ứng tuyển của công ty bạn.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * View detail of a specific application (GET /applications/{id})
     */
    public function show(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem chi tiết đơn ứng tuyển.");
        }

        $application = $this->applicationService->getApplicationDetail($user, $id);

        return Response::success($application, "Chi tiết đơn ứng tuyển.");
    }

    /**
     * Company updates application status (PATCH /applications/{id}/status & PUT /applications/{id}/status)
     */
    public function updateStatus(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để cập nhật trạng thái.");
        }

        $application = $this->applicationService->updateStatus($user, $id, $request->all());

        return Response::success($application, "Cập nhật trạng thái đơn ứng tuyển thành công.");
    }

    /**
     * Student withdraws application (POST /applications/{id}/withdraw & PATCH /applications/{id}/withdraw)
     */
    public function withdraw(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để rút đơn ứng tuyển.");
        }

        $application = $this->applicationService->withdraw($user, $id);

        return Response::success($application, "Rút đơn ứng tuyển thành công.");
    }

    public function update(Request $request, string $id): Response
    {
        return $this->updateStatus($request, $id);
    }

    public function destroy(Request $request, string $id): Response
    {
        return $this->withdraw($request, $id);
    }
}