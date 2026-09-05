<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\JobService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\CompanyRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Support\Pagination;

class JobController extends Controller
{
    private JobService $jobService;

    public function __construct()
    {
        $this->jobService = new JobService(new JobRepository(), new CompanyRepository());
    }

    public function index(Request $request): Response
    {
        $pagination = Pagination::fromParams($request->getParams);
        $filters = $request->getParams;
        $filters["is_public"] = true;

        $result = $this->jobService->search($filters, $pagination);

        return Response::success(
            $result["items"],
            "Lấy danh sách việc làm thành công.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    public function show(Request $request, string $id): Response
    {
        $user = $request->getUser();
        $isPrivileged = false;
        if ($user) {
            $role = $user["role"] ?? "";
            if ($role === "admin") {
                $isPrivileged = true;
            } elseif ($role === "company") {
                $company = (new \JobMarket\Infrastructure\CompanyRepository())->findByUserId($user["id"]);
                $rawJob = (new \JobMarket\Infrastructure\JobRepository())->findById($id);
                if ($company && $rawJob && ($rawJob["company_id"] === $company["id"])) {
                    $isPrivileged = true;
                }
            }
        }

        $job = $this->jobService->getById($id, $isPrivileged);

        return Response::success($job, "Chi tiết việc làm.");
    }

    public function store(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để đăng tin tuyển dụng.");
        }

        $job = $this->jobService->createJob($request->all(), $user);

        return Response::success(
            $job,
            "Đăng tin tuyển dụng thành công.",
            Response::HTTP_CREATED
        );
    }

    public function update(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để chỉnh sửa tin tuyển dụng.");
        }

        $job = $this->jobService->updateJob($id, $request->all(), $user);

        return Response::success($job, "Cập nhật tin tuyển dụng thành công.");
    }

    public function destroy(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xóa tin tuyển dụng.");
        }

        $this->jobService->deleteJob($id, $user);

        return Response::success(null, "Xóa tin tuyển dụng thành công.");
    }

    public function close(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để đóng tin tuyển dụng.");
        }

        $job = $this->jobService->closeJob($id, $user);

        return Response::success($job, "Đóng tin tuyển dụng thành công.");
    }

    public function myJobs(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem danh sách tin nội bộ.");
        }

        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->jobService->getMyJobs($user, $request->getParams, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách tin tuyển dụng của công ty.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    public function companyJobs(Request $request, string $id): Response
    {
        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->jobService->getCompanyJobs($id, $request->getParams, $pagination, false);

        return Response::success(
            $result["items"],
            "Danh sách tin tuyển dụng công khai của công ty.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }
}