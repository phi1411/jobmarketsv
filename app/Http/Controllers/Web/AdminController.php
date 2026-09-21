<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $html = View::renderWithLayout("admin/dashboard", [
            "title"       => "Tổng Quan Hệ Thống | JobMarketSV Quản Trị",
            "currentPage" => "admin_dashboard",
            "activeTab"   => "dashboard"
        ]);

        return Response::html($html);
    }

    public function users(Request $request): Response
    {
        $html = View::renderWithLayout("admin/users", [
            "title"       => "Quản Lý Người Dùng | JobMarketSV Quản Trị",
            "currentPage" => "admin_users",
            "activeTab"   => "users"
        ]);

        return Response::html($html);
    }

    public function companies(Request $request): Response
    {
        $html = View::renderWithLayout("admin/companies", [
            "title"       => "Kiểm Duyệt Doanh Nghiệp | JobMarketSV Quản Trị",
            "currentPage" => "admin_companies",
            "activeTab"   => "companies"
        ]);

        return Response::html($html);
    }

    public function jobs(Request $request): Response
    {
        $html = View::renderWithLayout("admin/jobs", [
            "title"       => "Kiểm Duyệt Tin Tuyển Dụng | JobMarketSV Quản Trị",
            "currentPage" => "admin_jobs",
            "activeTab"   => "jobs"
        ]);

        return Response::html($html);
    }

    public function auditLogs(Request $request): Response
    {
        $html = View::renderWithLayout("admin/audit_logs", [
            "title"       => "Nhật Ký Thao Tác Quản Trị | JobMarketSV Quản Trị",
            "currentPage" => "admin_audit_logs",
            "activeTab"   => "audit_logs"
        ]);

        return Response::html($html);
    }

    public function jobReports(Request $request): Response
    {
        $html = View::renderWithLayout("admin/job_reports", [
            "title"       => "Báo Cáo Tin Tuyển Dụng | JobMarketSV Quản Trị",
            "currentPage" => "admin_job_reports",
            "activeTab"   => "job_reports"
        ]);

        return Response::html($html);
    }

    public function support(Request $request): Response
    {
        $html = View::renderWithLayout("admin/support", [
            "title"       => "Hỗ Trợ Trực Tuyến | JobMarketSV Quản Trị",
            "currentPage" => "admin_support",
            "activeTab"   => "support"
        ]);

        return Response::html($html);
    }
}
