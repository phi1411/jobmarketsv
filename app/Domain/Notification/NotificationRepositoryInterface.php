<?php

namespace JobMarket\Domain\Notification;

use JobMarket\Support\Pagination;

interface NotificationRepositoryInterface
{
    public function create(Notification $notification): void;
    public function findById(string $id): ?array;
    public function getByUser(string $userId, ?Pagination $pagination = null): array;
    public function countByUser(string $userId): int;
    public function countUnreadByUser(string $userId): int;
    public function markAsRead(string $id, string $userId): bool;
    public function markAllAsRead(string $userId): int;
}