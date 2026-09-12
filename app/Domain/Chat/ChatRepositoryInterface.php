<?php

namespace JobMarket\Domain\Chat;

interface ChatRepositoryInterface
{
    public function findAdminUser(): ?array;

    public function getOrCreateSupportConversation(string $userId, string $adminId): array;

    public function getConversationById(string $conversationId): ?array;

    public function listAdminConversations(): array;

    public function getMessages(string $conversationId, ?string $afterId = null, int $limit = 50): array;

    public function createMessage(string $conversationId, string $senderId, string $content): array;

    public function markAsRead(string $conversationId, string $readerId): void;

    public function getUnreadCountForUser(string $userId): int;
}
