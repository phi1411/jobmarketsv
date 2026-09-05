<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\NotificationService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Support\Pagination;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Get list of notifications for the authenticated user (GET /notifications)
     */
    public function index(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem thông báo.");
        }

        $pagination = Pagination::fromParams($request->getParams);
        $result = $this->notificationService->getMyNotifications($user, $pagination);

        return Response::success(
            $result["items"],
            "Danh sách thông báo của bạn.",
            Response::HTTP_OK,
            $pagination->toMeta($result["total"])
        );
    }

    /**
     * Get unread notification count (GET /notifications/unread-count)
     */
    public function unreadCount(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để đếm thông báo chưa đọc.");
        }

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return Response::success(
            ["unread_count" => $unreadCount],
            "Số thông báo chưa đọc.",
            Response::HTTP_OK
        );
    }

    /**
     * Mark a notification as read (PATCH /notifications/{id}/read & PUT /notifications/{id}/read)
     */
    public function markAsRead(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để đánh dấu thông báo.");
        }

        $this->notificationService->markAsRead($user, $id);

        return Response::success(
            null,
            "Đã đánh dấu thông báo là đã đọc.",
            Response::HTTP_OK
        );
    }

    /**
     * Mark all notifications as read (PATCH /notifications/read-all & PUT /notifications/read-all)
     */
    public function markAllAsRead(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để đánh dấu tất cả thông báo.");
        }

        $count = $this->notificationService->markAllAsRead($user);

        return Response::success(
            ["marked_count" => $count],
            "Đã đánh dấu tất cả thông báo là đã đọc.",
            Response::HTTP_OK
        );
    }
}
