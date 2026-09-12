<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\Chat\ChatService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(?ChatService $chatService = null)
    {
        $this->chatService = $chatService ?? new ChatService();
    }

    /**
     * Get or create support conversation for the authenticated user (GET /api/support/conversation)
     */
    public function conversation(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để sử dụng tính năng nhắn tin.");
        }

        $conversation = $this->chatService->getOrCreateSupportConversation($user);

        return Response::success(
            $conversation,
            "Cuộc hội thoại hỗ trợ của bạn.",
            Response::HTTP_OK
        );
    }

    /**
     * Get messages in a conversation (GET /api/support/messages)
     * Query parameters: conversation_id (optional for student/company), after_id, limit
     */
    public function messages(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem tin nhắn.");
        }

        $conversationId = (string)$request->input("conversation_id", "");
        $afterId = $request->input("after_id");
        if ($afterId !== null && trim((string)$afterId) === "") {
            $afterId = null;
        }
        $limit = max(1, min(100, (int)$request->input("limit", 50)));

        // If no conversationId given, resolve support conversation for current user
        if (empty($conversationId)) {
            $conversation = $this->chatService->getOrCreateSupportConversation($user);
            $conversationId = $conversation["id"];
        }

        $messages = $this->chatService->getMessages($user, $conversationId, $afterId, $limit);

        return Response::success(
            [
                "conversation_id" => $conversationId,
                "messages"        => $messages,
                "count"           => count($messages)
            ],
            "Danh sách tin nhắn.",
            Response::HTTP_OK
        );
    }

    /**
     * Send a message (POST /api/support/messages)
     * Payload: conversation_id (optional for student/company), content
     */
    public function sendMessage(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để gửi tin nhắn.");
        }

        $conversationId = (string)$request->input("conversation_id", "");
        $content = (string)$request->input("content", "");

        if (trim($content) === "") {
            throw new ValidationException("Nội dung tin nhắn không được để trống.");
        }

        if (empty($conversationId)) {
            $conversation = $this->chatService->getOrCreateSupportConversation($user);
            $conversationId = $conversation["id"];
        }

        $message = $this->chatService->sendMessage($user, $conversationId, $content);

        return Response::success(
            $message,
            "Gửi tin nhắn thành công.",
            Response::HTTP_CREATED
        );
    }

    /**
     * Mark conversation as read (POST /api/support/read)
     */
    public function markRead(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập.");
        }

        $conversationId = (string)$request->input("conversation_id", "");
        if (empty($conversationId)) {
            throw new ValidationException("Thiếu conversation_id.");
        }

        $this->chatService->markAsRead($user, $conversationId);

        return Response::success(
            null,
            "Đã đánh dấu đã đọc.",
            Response::HTTP_OK
        );
    }

    /**
     * Admin endpoint: List all support conversations (GET /api/admin/support/conversations)
     */
    public function adminConversations(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập.");
        }

        $conversations = $this->chatService->listAdminConversations($user);

        return Response::success(
            $conversations,
            "Danh sách hội thoại hỗ trợ.",
            Response::HTTP_OK
        );
    }

    /**
     * Get unread messages count for current user (GET /api/support/unread-count)
     */
    public function unreadCount(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            return Response::success(["unread_count" => 0]);
        }

        $count = $this->chatService->getUnreadCount($user);

        return Response::success(
            ["unread_count" => $count],
            "Số tin nhắn chưa đọc.",
            Response::HTTP_OK
        );
    }
}
