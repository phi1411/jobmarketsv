<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\Cv\StudentCvService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Middlewares\RoleMiddleware;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class StudentCvController extends Controller
{
    private StudentCvService $cvService;

    public function __construct(?StudentCvService $cvService = null)
    {
        $this->cvService = $cvService ?? new StudentCvService();
    }

    /**
     * GET /student/cv
     * Returns the active CV metadata for the authenticated student.
     */
    public function show(Request $request): Response
    {
        $user = $this->requireAuthenticatedStudent($request);
        $cvData = $this->cvService->getActiveCv($user);

        return Response::success(
            $cvData,
            $cvData ? "Thông tin CV hiện tại." : "Chưa có CV nào được tải lên."
        );
    }

    /**
     * POST /student/cv
     * Handles multipart PDF upload or replacement for the authenticated student.
     */
    public function upload(Request $request): Response
    {
        $user = $this->requireAuthenticatedStudent($request);

        // Check common multipart form file keys
        $file = $request->file("cv_file") ?? $request->file("file") ?? $request->file("cv");

        $metadata = $this->cvService->uploadActiveCv($user, $file);

        return Response::success($metadata, "Tải lên CV thành công.");
    }

    /**
     * DELETE /student/cv
     * Detaches the active CV from the authenticated student's profile.
     */
    public function destroy(Request $request): Response
    {
        $user = $this->requireAuthenticatedStudent($request);
        $this->cvService->deleteActiveCv($user);

        return Response::success(null, "Xóa CV thành công.");
    }

    /**
     * Enforce authentication and student role.
     * @throws AuthenticationException
     * @throws \JobMarket\Exceptions\AuthorizationException
     */
    private function requireAuthenticatedStudent(Request $request): array
    {
        $user = $request->getUser();
        if ($user === null) {
            throw new AuthenticationException("Vui lòng đăng nhập để thực hiện chức năng này.");
        }

        RoleMiddleware::check($request, ["student", "developer"]);

        return $user;
    }
}
