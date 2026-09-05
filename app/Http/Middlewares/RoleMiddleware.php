<?php

namespace JobMarket\Http\Middlewares;

use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Http\Request;

class RoleMiddleware
{
    /**
     * Check if authenticated user has one of the allowed roles
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public static function check(Request $request, array|string $allowedRoles): void
    {
        $user = $request->getUser();
        if ($user === null) {
            throw new AuthenticationException("Vui lòng đăng nhập để thực hiện chức năng này.");
        }

        $roles = is_array($allowedRoles) ? $allowedRoles : explode(",", $allowedRoles);
        $userRole = strtolower((string)($user["role"] ?? ""));

        if (!in_array($userRole, array_map("strtolower", $roles), true)) {
            throw new AuthorizationException("Bạn không có quyền truy cập chức năng này (yêu cầu vai trò: " . implode(", ", $roles) . ").");
        }
    }

    /**
     * Verify resource ownership
     * @throws AuthorizationException
     */
    public static function checkOwnership(Request $request, string $ownerId, string $message = "Bạn không có quyền chỉnh sửa tài nguyên của người khác."): void
    {
        $user = $request->getUser();
        $currentUserId = (string)($user["id"] ?? "");
        $currentUserRole = strtolower((string)($user["role"] ?? ""));

        // Admin has universal override
        if ($currentUserRole === "admin") {
            return;
        }

        if ($currentUserId !== $ownerId) {
            throw new AuthorizationException($message);
        }
    }
}
