<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\FavoriteService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class FavoriteController extends Controller
{
    private FavoriteService $favoriteService;

    public function __construct()
    {
        $this->favoriteService = new FavoriteService();
    }

    /**
     * Get list of favorited jobs for the authenticated student (GET /favorites/jobs)
     */
    public function index(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem danh sách yêu thích.");
        }

        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->favoriteService->getMyFavorites($user, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách việc làm yêu thích của bạn.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Add a job to favorites (POST /favorites/jobs/{jobId})
     */
    public function store(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để lưu việc làm yêu thích.");
        }

        $result = $this->favoriteService->addFavorite($user, $id);

        return Response::success(
            $result,
            $result["message"],
            $result["is_new"] ? Response::HTTP_CREATED : Response::HTTP_OK
        );
    }

    /**
     * Remove a job from favorites (DELETE /favorites/jobs/{jobId})
     */
    public function destroy(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xóa việc làm yêu thích.");
        }

        $this->favoriteService->removeFavorite($user, $id);

        return Response::success(null, "Đã xóa việc làm khỏi danh sách yêu thích.");
    }
}