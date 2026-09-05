<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Notification\Notification;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Infrastructure\NotificationRepository;
use JobMarket\Support\Pagination;

class NotificationService
{
    private NotificationRepository $notificationRepo;

    public function __construct(?NotificationRepository $notificationRepo = null)
    {
        $this->notificationRepo = $notificationRepo ?? new NotificationRepository();
    }

    public function notify(
        string $userId,
        string $title,
        string $message,
        string $type = "general",
        ?array $data = null
    ): Notification {
        $notification = Notification::create($userId, $title, $message, $type, $data);
        $this->notificationRepo->create($notification);

        return $notification;
    }

    public function getMyNotifications(array $user, ?Pagination $pagination = null): array
    {
        $userId = $user["id"] ?? "";
        $rows = $this->notificationRepo->getByUser($userId, $pagination);
        $total = $this->notificationRepo->countByUser($userId);

        $items = array_map(function($row) {
            return Notification::fromArray($row)->toArray();
        }, $rows);

        return [
            "items" => $items,
            "total" => $total
        ];
    }

    public function getUnreadCount(array $user): int
    {
        $userId = $user["id"] ?? "";
        return $this->notificationRepo->countUnreadByUser($userId);
    }

    public function markAsRead(array $user, string $id): void
    {
        $userId = $user["id"] ?? "";
        $row = $this->notificationRepo->findById($id);

        if (!$row) {
            throw new NotFoundException("Không tìm thấy thông báo.");
        }

        if ($row["user_id"] !== $userId) {
            throw new AuthorizationException("Bạn không có quyền đánh dấu thông báo của người khác.");
        }

        $this->notificationRepo->markAsRead($id, $userId);
    }

    public function markAllAsRead(array $user): int
    {
        $userId = $user["id"] ?? "";
        return $this->notificationRepo->markAllAsRead($userId);
    }
}
