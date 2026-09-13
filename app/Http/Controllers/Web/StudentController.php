<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\View;

class StudentController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $html = View::renderWithLayout("student/dashboard", [
            "title"       => "Tổng Quan Sinh Viên | JobMarketplace",
            "currentPage" => "student_dashboard",
            "activeTab"   => "dashboard"
        ]);

        return Response::html($html);
    }

    public function profile(Request $request): Response
    {
        $html = View::renderWithLayout("student/profile", [
            "title"       => "Hồ Sơ Cá Nhân | JobMarketplace",
            "currentPage" => "student_profile",
            "activeTab"   => "profile"
        ]);

        return Response::html($html);
    }

    public function cvs(Request $request): Response
    {
        $html = View::renderWithLayout("student/cv_list", [
            "title"       => "Quản Lý CV Online | JobMarketplace",
            "currentPage" => "student_cvs",
            "activeTab"   => "cvs"
        ]);

        return Response::html($html);
    }

    public function editCv(Request $request, string $id): Response
    {
        $html = View::renderWithLayout("student/cv_edit", [
            "title"       => "Chỉnh Sửa CV Online | JobMarketplace",
            "currentPage" => "student_cv_edit",
            "activeTab"   => "cvs",
            "cvId"        => $id
        ]);

        return Response::html($html);
    }

    public function applications(Request $request): Response
    {
        $html = View::renderWithLayout("student/applications", [
            "title"       => "Việc Đã Ứng Tuyển | JobMarketplace",
            "currentPage" => "student_applications",
            "activeTab"   => "applications"
        ]);

        return Response::html($html);
    }

    public function favorites(Request $request): Response
    {
        $html = View::renderWithLayout("student/favorites", [
            "title"       => "Việc Làm Yêu Thích | JobMarketplace",
            "currentPage" => "student_favorites",
            "activeTab"   => "favorites"
        ]);

        return Response::html($html);
    }

    public function savedSearches(Request $request): Response
    {
        $html = View::renderWithLayout("student/saved_searches", [
            "title"       => "Bộ Lọc Đã Lưu | JobMarketplace",
            "currentPage" => "student_saved_searches",
            "activeTab"   => "saved_searches"
        ]);

        return Response::html($html);
    }

    public function notifications(Request $request): Response
    {
        $html = View::renderWithLayout("student/notifications", [
            "title"       => "Thông Báo Của Tôi | JobMarketplace",
            "currentPage" => "student_notifications",
            "activeTab"   => "notifications"
        ]);

        return Response::html($html);
    }

    public function recommendations(Request $request): Response
    {
        $html = View::renderWithLayout("student/recommendations", [
            "title"       => "Việc Làm Dành Cho Bạn | JobMarketplace",
            "currentPage" => "student_recommendations",
            "activeTab"   => "recommendations"
        ]);

        return Response::html($html);
    }
}
