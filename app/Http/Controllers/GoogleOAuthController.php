<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\Authentication\GoogleOAuthService;
use JobMarket\Exceptions\AppException;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use Throwable;

class GoogleOAuthController extends Controller
{
    private GoogleOAuthService $oauthService;

    public function __construct(?GoogleOAuthService $oauthService = null)
    {
        $this->oauthService = $oauthService ?? new GoogleOAuthService();
    }

    public function start(Request $request): Response
    {
        $role = (string)($request->input("role") ?? $request->input("intent") ?? "");
        $returnUrl = $request->input("return_url") ?? $request->input("redirect") ?? null;

        try {
            $authUrl = $this->oauthService->start($role, $returnUrl);
            return Response::redirect($authUrl);
        } catch (ValidationException $e) {
            if (!$request->wantsHtml()) {
                return Response::error($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, $e->getErrors());
            }
            return Response::redirect("/login?error=" . urlencode($e->getMessage()));
        } catch (Throwable $e) {
            if (!$request->wantsHtml()) {
                return Response::error($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            return Response::redirect("/login?error=" . urlencode($e->getMessage()));
        }
    }

    public function callback(Request $request): Response
    {
        // Check for Google error response (e.g. access_denied)
        $error = $request->input("error");
        if (!empty($error)) {
            $msg = "Đăng nhập bằng tài khoản Google không thành công hoặc đã bị hủy.";
            if ($request->wantsHtml()) {
                return Response::redirect("/login?error=" . urlencode($msg));
            }
            return Response::error($msg, Response::HTTP_BAD_REQUEST);
        }

        $code = (string)$request->input("code", "");
        $state = (string)$request->input("state", "");

        try {
            $result = $this->oauthService->callback($code, $state);
            $jwtToken = $result["token"];
            $user = $result["user"];
            $redirectUrl = $result["redirect"];

            if (!$request->wantsHtml()) {
                return Response::success([
                    "token"    => $jwtToken,
                    "user"     => $user,
                    "redirect" => $redirectUrl
                ], "Đăng nhập bằng Google thành công.");
            }

            // Safe client-side handoff: store token & user profile in localStorage, then redirect
            $jsonToken = json_encode($jwtToken);
            $jsonUser = json_encode(json_encode($user));
            $jsonRedirect = json_encode($redirectUrl);

            $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang xử lý đăng nhập...</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc; color: #334155; }
        .spinner { border: 3px solid #e2e8f0; border-top: 3px solid #3b82f6; border-radius: 50%; width: 36px; height: 36px; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div style="text-align:center;">
        <div class="spinner"></div>
        <p>Đang hoàn tất đăng nhập, vui lòng đợi trong giây lát...</p>
    </div>
    <script>
        try {
            localStorage.setItem("jobmarket_token", {$jsonToken});
            localStorage.setItem("jobmarket_user", {$jsonUser});
            window.location.replace({$jsonRedirect});
        } catch (e) {
            window.location.replace("/login?error=" + encodeURIComponent("Không thể lưu phiên đăng nhập."));
        }
    </script>
</body>
</html>
HTML;

            return Response::html($html);
        } catch (AuthenticationException $e) {
            if (!$request->wantsHtml()) {
                // If banned or suspended, status code is 403, otherwise 401
                $status = ($e->getMessage() === "Tài khoản của bạn đã bị khóa." || $e->getMessage() === "Tài khoản của bạn đang bị tạm khóa.")
                    ? Response::HTTP_FORBIDDEN
                    : Response::HTTP_UNAUTHORIZED;
                return Response::error($e->getMessage(), $status);
            }
            return Response::redirect("/login?error=" . urlencode($e->getMessage()));
        } catch (ValidationException $e) {
            if (!$request->wantsHtml()) {
                return Response::error($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, $e->getErrors());
            }
            return Response::redirect("/login?error=" . urlencode($e->getMessage()));
        } catch (Throwable $e) {
            if (!$request->wantsHtml()) {
                return Response::error($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            return Response::redirect("/login?error=" . urlencode("Đã có lỗi xảy ra trong quá trình xác thực với Google."));
        }
    }
}
