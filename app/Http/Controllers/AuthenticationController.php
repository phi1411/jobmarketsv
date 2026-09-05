<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\AuthenticationService;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Http\Validation\Validator;
use JobMarket\Infrastructure\AuthenticationRepository;

class AuthenticationController extends Controller
{
    private AuthenticationService $authService;

    public function __construct()
    {
        $this->authService = new AuthenticationService(new AuthenticationRepository());
    }

    public function register(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            "name"     => "required|min:2|max:100",
            "email"    => "required|email",
            "password" => "required|min:6",
            "role"     => "required|in:student,developer,company,employer,admin"
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $role = $request->input("role");
        if ($role === "developer") $role = "student";
        if ($role === "employer") $role = "company";

        $this->authService->register([
            "name"     => trim((string)$request->input("name")),
            "email"    => strtolower(trim((string)$request->input("email"))),
            "password" => (string)$request->input("password"),
            "role"     => $role
        ]);

        return Response::created(null, "Đăng ký tài khoản thành công. Vui lòng đăng nhập.");
    }

    public function login(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            "email"    => "required|email",
            "password" => "required"
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $loginData = $this->authService->loginDetails([
            "email"    => strtolower(trim((string)$request->input("email"))),
            "password" => (string)$request->input("password")
        ]);

        return Response::success($loginData, "Đăng nhập thành công.");
    }

    public function logout(Request $request): Response
    {
        $user = $request->getUser();
        $email = $user["email"] ?? $request->input("email");

        if (!empty($email)) {
            $this->authService->logout((string)$email);
        }

        return Response::success(null, "Đăng xuất thành công.");
    }
}
