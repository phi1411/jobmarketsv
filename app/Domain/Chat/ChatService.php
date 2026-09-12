<?php

namespace JobMarket\Domain\Chat;

use JobMarket\Domain\Chat\ChatRepositoryInterface;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Infrastructure\ChatRepository;

class ChatService
{
    private ChatRepositoryInterface $chatRepo;

    public function __construct(?ChatRepositoryInterface $chatRepo = null)
    {
        $this->chatRepo = $chatRepo ?? new ChatRepository();
    }

    /**
     * Get or create a support conversation for student or company with admin
     */
    public function getOrCreateSupportConversation(array $user): array
    {
        $userId = $user["id"] ?? "";
        if (empty($userId)) {
            throw new AuthorizationException("Vui lòng đăng nhập để sử dụng tính năng nhắn tin.");
        }

        $admin = $this->chatRepo->findAdminUser();
        if (!$admin) {
            throw new NotFoundException("Hệ thống hiện chưa cấu hình tài khoản quản trị.");
        }

        $conversation = $this->chatRepo->getOrCreateSupportConversation($userId, $admin["id"]);
        $conversation["admin"] = [
            "id"    => $admin["id"],
            "name"  => $admin["name"] ?? "Quản Trị Viên",
            "email" => $admin["email"] ?? "admin@jobmarket.vn",
            "role"  => "admin"
        ];

        return $conversation;
    }

    /**
     * Send a message to a conversation
     */
    public function sendMessage(array $user, string $conversationId, string $content): array
    {
        $userId = $user["id"] ?? "";
        if (empty($userId)) {
            throw new AuthorizationException("Vui lòng đăng nhập để gửi tin nhắn.");
        }

        $content = trim($content);
        if ($content === "") {
            throw new ValidationException("Nội dung tin nhắn không được để trống.");
        }

        if (mb_strlen($content) > 2000) {
            throw new ValidationException("Nội dung tin nhắn tối đa 2000 ký tự.");
        }

        $conversation = $this->chatRepo->getConversationById($conversationId);
        if (!$conversation) {
            throw new NotFoundException("Cuộc trò chuyện không tồn tại.");
        }

        // Verify user is a participant or is admin
        $isParticipant = ($conversation["sender_id"] === $userId || $conversation["recipient_id"] === $userId);
        $isAdmin = (($user["role"] ?? "") === "admin");

        if (!$isParticipant && !$isAdmin) {
            throw new AuthorizationException("Bạn không có quyền gửi tin nhắn trong cuộc trò chuyện này.");
        }

        return $this->chatRepo->createMessage($conversationId, $userId, $content);
    }

    /**
     * Get messages with optional incremental polling (after_id)
     */
    public function getMessages(array $user, string $conversationId, ?string $afterId = null, int $limit = 50): array
    {
        $userId = $user["id"] ?? "";
        if (empty($userId)) {
            throw new AuthorizationException("Vui lòng đăng nhập để xem tin nhắn.");
        }

        $conversation = $this->chatRepo->getConversationById($conversationId);
        if (!$conversation) {
            throw new NotFoundException("Cuộc trò chuyện không tồn tại.");
        }

        $isParticipant = ($conversation["sender_id"] === $userId || $conversation["recipient_id"] === $userId);
        $isAdmin = (($user["role"] ?? "") === "admin");

        if (!$isParticipant && !$isAdmin) {
            throw new AuthorizationException("Bạn không có quyền xem cuộc trò chuyện này.");
        }

        $messages = $this->chatRepo->getMessages($conversationId, $afterId, $limit);

        // Mark incoming messages as read
        $this->chatRepo->markAsRead($conversationId, $userId);

        return $messages;
    }

    /**
     * List all conversations for Admin dashboard
     */
    public function listAdminConversations(array $user): array
    {
        if (($user["role"] ?? "") !== "admin") {
            throw new AuthorizationException("Chỉ quản trị viên mới có quyền xem danh sách hỗ trợ.");
        }

        return $this->chatRepo->listAdminConversations();
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(array $user, string $conversationId): void
    {
        $userId = $user["id"] ?? "";
        if (!empty($userId)) {
            $this->chatRepo->markAsRead($conversationId, $userId);
        }
    }

    /**
     * Get count of unread messages for user
     */
    public function getUnreadCount(array $user): int
    {
        $userId = $user["id"] ?? "";
        if (empty($userId)) {
            return 0;
        }
        return $this->chatRepo->getUnreadCountForUser($userId);
    }
}
