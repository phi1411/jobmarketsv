<?php

namespace JobMarket\Http\Controllers\Web;

use JobMarket\Http\Controllers\Controller;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        $appEnv = strtolower(trim((string)($_ENV["APP_ENV"] ?? "production")));
        $isDev = in_array($appEnv, ["development", "local", "dev", "test"]);

        $html = View::renderWithLayout("auth/login", [
            "title"       => "Đăng Nhập | JobMarketSV",
            "currentPage" => "login",
            "isDev"       => $isDev
        ]);

        return Response::html($html);
    }

    public function showRegister(Request $request): Response
    {
        $html = View::renderWithLayout("auth/register", [
            "title"       => "Đăng Ký Tài Khoản | JobMarketSV",
            "currentPage" => "register"
        ]);

        return Response::html($html);
    }

    public function logout(Request $request): Response
    {
        // Render a small view that clears localStorage token and redirects to /login
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Đang đăng xuất...</title></head><body>
            <script>
                localStorage.removeItem("jobmarket_token");
                localStorage.removeItem("jobmarket_user");
                window.location.href = "/login?logged_out=1";
            </script>
        </body></html>';

        return Response::html($html);
    }
}
