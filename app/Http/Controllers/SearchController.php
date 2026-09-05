<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\SaveSearchService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class SearchController extends Controller
{
    private SaveSearchService $searchService;

    public function __construct()
    {
        $this->searchService = new SaveSearchService();
    }

    /**
     * Get list of saved searches for the authenticated student (GET /saved-searches)
     */
    public function index(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem danh sách tìm kiếm đã lưu.");
        }

        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->searchService->list($user, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách các bộ lọc tìm kiếm đã lưu của bạn.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Create a new saved search (POST /saved-searches)
     */
    public function store(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để lưu bộ lọc tìm kiếm.");
        }

        $search = $this->searchService->create($user, $request->all());

        return Response::success(
            $search,
            "Lưu bộ lọc tìm kiếm thành công.",
            Response::HTTP_CREATED
        );
    }

    /**
     * Update an existing saved search (PATCH /saved-searches/{id} & PUT /saved-searches/{id})
     */
    public function update(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để cập nhật tìm kiếm đã lưu.");
        }

        $search = $this->searchService->update($user, $id, $request->all());

        return Response::success(
            $search,
            "Cập nhật bộ lọc tìm kiếm đã lưu thành công.",
            Response::HTTP_OK
        );
    }

    /**
     * Delete a saved search (DELETE /saved-searches/{id})
     */
    public function destroy(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xóa tìm kiếm đã lưu.");
        }

        $this->searchService->delete($user, $id);

        return Response::success(null, "Xóa bộ lọc tìm kiếm đã lưu thành công.");
    }
}