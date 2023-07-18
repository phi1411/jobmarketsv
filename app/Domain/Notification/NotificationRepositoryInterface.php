<?php

namespace JobMarket\Domain\Notification;

interface NotificationRepositoryInterface
{
    public function getAll(): array;
    public function create(Notification $notif): void;
    public function getById(string $id): array;
    public function update(Notification $notif): void;
    public function delete(string $id): void;
}