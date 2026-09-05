<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\View;

class CompanyController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $html = View::renderWithLayout("company/dashboard", [
            "title"       => "Tổng Quan Nhà Tuyển Dụng | JobMarketplace",
            "currentPage" => "company_dashboard",
            "activeTab"   => "dashboard"
        ]);

        return Response::html($html);
    }

    public function profile(Request $request): Response
    {
        $html = View::renderWithLayout("company/profile", [
            "title"       => "Hồ Sơ Doanh Nghiệp | JobMarketplace",
            "currentPage" => "company_profile",
            "activeTab"   => "profile"
        ]);

        return Response::html($html);
    }

    public function jobs(Request $request): Response
    {
        $html = View::renderWithLayout("company/jobs", [
            "title"       => "Quản Lý Tin Tuyển Dụng | JobMarketplace",
            "currentPage" => "company_jobs",
            "activeTab"   => "jobs"
        ]);

        return Response::html($html);
    }

    public function createJob(Request $request): Response
    {
        $html = View::renderWithLayout("company/job_form", [
            "title"       => "Đăng Tin Tuyển Dụng Mới | JobMarketplace",
            "currentPage" => "company_jobs_create",
            "activeTab"   => "jobs",
            "isEdit"      => false,
            "jobId"       => null
        ]);

        return Response::html($html);
    }

    public function editJob(Request $request, string $id): Response
    {
        $html = View::renderWithLayout("company/job_form", [
            "title"       => "Chỉnh Sửa Tin Tuyển Dụng | JobMarketplace",
            "currentPage" => "company_jobs_edit",
            "activeTab"   => "jobs",
            "isEdit"      => true,
            "jobId"       => $id
        ]);

        return Response::html($html);
    }

    public function applications(Request $request): Response
    {
        $html = View::renderWithLayout("company/applications", [
            "title"       => "Hồ Sơ Ứng Tuyển Nhận Được | JobMarketplace",
            "currentPage" => "company_applications",
            "activeTab"   => "applications"
        ]);

        return Response::html($html);
    }

    public function notifications(Request $request): Response
    {
        $html = View::renderWithLayout("company/notifications", [
            "title"       => "Thông Báo Doanh Nghiệp | JobMarketplace",
            "currentPage" => "company_notifications",
            "activeTab"   => "notifications"
        ]);

        return Response::html($html);
    }
}
