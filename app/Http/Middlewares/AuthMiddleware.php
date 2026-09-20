<?php

namespace JobMarket\Http\Middlewares;

use JobMarket\Facades\JWT;
use JobMarket\Facades\Session;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\AuthenticationRepository;

class AuthMiddleware implements MiddlewareInterface
{
    private string $next = \JobMarket\Http\Kernel::class;

    /**
     * Routes accessible publicly without authentication
     */
    private array $publicRoutes = [
        "GET"  => ["/", "/jobs", "/categories", "/skills", "/locations", "/locations/hierarchy", "/locations/administrative", "/developers", "/viec-lam", "/mau-cv-sinh-vien", "/cv/templates", "/login", "/register", "/logout", "/auth/google/start", "/auth/google/callback", "/map/places/autocomplete"],
        "POST" => ["/login", "/register", "/assistant/chat", "/assistant/feedback", "/jobs/nearby-search", "/map/places/detail", "/map/geocode", "/map/reverse-geocode", "/map/resolve-location"]
    ];

    public function __invoke(Request $request): Response
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod() === "HEAD" ? "GET" : $request->getMethod();

        // Always allow CORS Pre-flight OPTIONS request without token
        if ($method === "OPTIONS") {
            return call_user_func([new $this->next, "__invoke"], $request);
        }

        $token = Session::token();
        $userPayload = null;
        $dbUser = null;

        if ($token !== null) {
            $userPayload = JWT::decode($token);
            if ($userPayload !== null) {
                $authRepo = new AuthenticationRepository();
                $dbUser = $authRepo->findUserRecordByIdOrEmail(
                    $userPayload["id"] ?? null,
                    $userPayload["email"] ?? null
                );
            }
        }

        // Check if route is public
        $isPublic = false;
        if (isset($this->publicRoutes[$method])) {
            foreach ($this->publicRoutes[$method] as $publicRoute) {
                if ($path === $publicRoute) {
                    $isPublic = true;
                    break;
                }
            }
        }

        // Allow GET /jobs/{id}, GET /viec-lam/{id}, GET /companies/{id}/jobs, and GET /developers/{id} publicly
        if ($method === "GET" && (
            preg_match('#^/jobs/[0-9a-zA-Z\-_]+$#', $path) ||
            preg_match('#^/jobs/[0-9a-zA-Z\-_]+/locations$#', $path) ||
            preg_match('#^/viec-lam/[0-9a-zA-Z\-_]+$#', $path) ||
            preg_match('#^/companies/[0-9a-zA-Z\-_]+/jobs$#', $path) ||
            preg_match('#^/developers/[0-9a-zA-Z\-_]+$#', $path) ||
            preg_match('#^/cv/[0-9a-zA-Z\-_]+(?:/export\.pdf)?$#', $path) ||
            ($request->wantsHtml() && (str_starts_with($path, "/student/") || str_starts_with($path, "/company/") || str_starts_with($path, "/admin/")))
        )) {
            $isPublic = true;
        }

        // For public routes, continue even without token or with invalid/revoked token
        if ($isPublic) {
            if ($dbUser !== null && ($dbUser["status"] ?? "active") === "active") {
                $isValidToken = !empty($dbUser["token"]) &&
                    $dbUser["token"] !== "revoked" &&
                    $dbUser["token"] === $token &&
                    !empty($dbUser["token_expires_at"]) &&
                    strtotime($dbUser["token_expires_at"]) > time();

                if ($isValidToken) {
                    $userPayload["id"] = $dbUser["id"];
                    $userPayload["email"] = $dbUser["email"];
                    $userPayload["role"] = $dbUser["role"];
                    $request->setUser($userPayload);
                }
            }
            return call_user_func([new $this->next, "__invoke"], $request);
        }

        // For protected routes:
        // 1. Require valid token syntax and signature
        if ($token === null || $userPayload === null) {
            return Response::error("Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.", Response::HTTP_UNAUTHORIZED);
        }

        // 2. User record must exist in database
        if ($dbUser === null) {
            return Response::error("Tài khoản không tồn tại trên hệ thống.", Response::HTTP_UNAUTHORIZED);
        }

        // 3. User status policy: active only
        $status = $dbUser["status"] ?? "active";
        if ($status === "banned") {
            return Response::error("Tài khoản của bạn đã bị khóa.", Response::HTTP_FORBIDDEN);
        }
        if ($status === "suspended") {
            return Response::error("Tài khoản của bạn đang bị tạm khóa.", Response::HTTP_FORBIDDEN);
        }
        if ($status !== "active") {
            return Response::error("Tài khoản chưa được kích hoạt hoặc không hợp lệ.", Response::HTTP_FORBIDDEN);
        }

        // 4. Token validation policy: Fail-closed.
        // Protected routes require an active token in the DB that matches Bearer token exactly and has not expired.
        if (empty($dbUser["token"]) || $dbUser["token"] === "revoked") {
            return Response::error("Phiên đăng nhập không hợp lệ hoặc đã kết thúc. Vui lòng đăng nhập lại.", Response::HTTP_UNAUTHORIZED);
        }

        if ($dbUser["token"] !== $token) {
            return Response::error("Phiên đăng nhập không hợp lệ hoặc đã bị thay thế. Vui lòng đăng nhập lại.", Response::HTTP_UNAUTHORIZED);
        }

        if (empty($dbUser["token_expires_at"]) || strtotime($dbUser["token_expires_at"]) <= time()) {
            return Response::error("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.", Response::HTTP_UNAUTHORIZED);
        }

        // Synchronize authoritative user data from database into request
        $userPayload["id"] = $dbUser["id"];
        $userPayload["email"] = $dbUser["email"];
        $userPayload["role"] = $dbUser["role"];
        $request->setUser($userPayload);

        return call_user_func([new $this->next, "__invoke"], $request);
    }
}
