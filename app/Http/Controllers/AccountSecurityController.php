<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\AccountSecurityService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\Security\FileRateLimiter;

class AccountSecurityController extends Controller
{
    private AccountSecurityService $service;
    private FileRateLimiter $limiter;

    public function __construct()
    {
        $this->service = new AccountSecurityService();
        $this->limiter = new FileRateLimiter();
    }

    public function status(Request $request): Response
    {
        return Response::success($this->service->passwordStatus($this->user($request)), "Trạng thái bảo mật tài khoản.");
    }

    public function requestCode(Request $request): Response
    {
        $user = $this->user($request);
        if (($limited = $this->limit($request, $user, "send", 5, 3600)) !== null) return $limited;
        return Response::success($this->service->requestChangeCode($user, $request->all()), "Mã xác nhận đã được gửi tới email của bạn.");
    }

    public function verifyCode(Request $request): Response
    {
        $user = $this->user($request);
        if (($limited = $this->limit($request, $user, "verify", 10, 600)) !== null) return $limited;
        return Response::success($this->service->verifyChangeCode($user, $request->all()), "Mã email đã được xác nhận.");
    }

    public function updatePassword(Request $request): Response
    {
        $user = $this->user($request);
        if (($limited = $this->limit($request, $user, "update", 5, 900)) !== null) return $limited;
        return Response::success($this->service->updatePassword($user, $request->all()), "Mật khẩu đã được cập nhật. Vui lòng đăng nhập lại.");
    }

    private function user(Request $request): array
    {
        $user = $request->getUser();
        if (!$user) throw new AuthenticationException();
        return $user;
    }

    private function limit(Request $request, array $user, string $action, int $max, int $seconds): ?Response
    {
        $key = "password-security:{$action}:" . ($user["id"] ?? "unknown") . ":" . hash("sha256", $request->getClientIp());
        $result = $this->limiter->consume($key, $max, $seconds);
        if ($result->allowed) return null;
        if ($result->failedClosed) return Response::error("Hệ thống kiểm soát bảo mật tạm thời không khả dụng.", 503);
        return Response::error("Bạn thao tác quá nhiều lần. Vui lòng thử lại sau.", 429, ["retry_after_seconds" => $result->retryAfter], ["Retry-After" => (string)$result->retryAfter]);
    }
}
