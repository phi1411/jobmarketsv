<?php

namespace JobMarket\Http\Middlewares;

use JobMarket\Facades\JWT;
use JobMarket\Facades\Session;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private string $next = \JobMarket\Http\Kernel::class;

    /**
     * Routes accessible publicly without authentication
     */
    private array $publicRoutes = [
        "GET"  => ["/", "/jobs", "/categories", "/skills", "/locations", "/developers", "/viec-lam", "/login", "/register", "/logout"],
        "POST" => ["/login", "/register"]
    ];

    public function __invoke(Request $request): Response
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        // Always allow CORS Pre-flight OPTIONS request without token
        if ($method === "OPTIONS") {
            return call_user_func([new $this->next, "__invoke"], $request);
        }

        $token = Session::token();
        $userPayload = null;

        if ($token !== null) {
            $userPayload = JWT::decode($token);
            if ($userPayload !== null) {
                $request->setUser($userPayload);
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
            preg_match('#^/viec-lam/[0-9a-zA-Z\-_]+$#', $path) ||
            preg_match('#^/companies/[0-9a-zA-Z\-_]+/jobs$#', $path) ||
            preg_match('#^/developers/[0-9a-zA-Z\-_]+$#', $path) ||
            ($request->wantsHtml() && (str_starts_with($path, "/student/") || str_starts_with($path, "/company/") || str_starts_with($path, "/admin/")))
        )) {
            $isPublic = true;
        }

        // For public routes, continue even without token
        if ($isPublic) {
            return call_user_func([new $this->next, "__invoke"], $request);
        }

        // For protected routes, require valid token
        if ($token === null || $userPayload === null) {
            return Response::error("Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.", Response::HTTP_UNAUTHORIZED);
        }

        return call_user_func([new $this->next, "__invoke"], $request);
    }
}
