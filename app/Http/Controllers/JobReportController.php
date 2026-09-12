<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\JobReportService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class JobReportController extends Controller
{
    private JobReportService $service;

    public function __construct()
    {
        $this->service = new JobReportService();
    }

    public function store(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) throw new AuthenticationException("Vui lòng đăng nhập để báo cáo tin.");
        return Response::success($this->service->report($user, $id, $request->all()), "Đã gửi báo cáo tới quản trị viên.", Response::HTTP_CREATED);
    }

    public function index(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) throw new AuthenticationException("Vui lòng đăng nhập với quyền quản trị viên.");
        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->service->list($user, $request->getParams, $pagination);
        return Response::success($result["items"], "Hàng đợi báo cáo tin tuyển dụng.", Response::HTTP_OK, $pagination->toMeta($result["total"]));
    }

    public function update(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) throw new AuthenticationException("Vui lòng đăng nhập với quyền quản trị viên.");
        return Response::success($this->service->resolve($user, $id, $request->all()), "Đã cập nhật kết quả xử lý báo cáo.");
    }
}
