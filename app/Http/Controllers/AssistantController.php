<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\Assistant\AssistantService;
use JobMarket\Domain\Assistant\Exceptions\GeminiBlockedContentException;
use JobMarket\Domain\Assistant\Exceptions\GeminiException;
use JobMarket\Domain\Assistant\Exceptions\GeminiRateLimitException;
use JobMarket\Domain\Assistant\Exceptions\GeminiTimeoutException;
use JobMarket\Domain\Assistant\Exceptions\GeminiUnavailableException;
use JobMarket\Domain\Assistant\RateLimiterInterface;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\Security\FileRateLimiter;

class AssistantController extends Controller
{
    private AssistantService $assistantService;
    private RateLimiterInterface $rateLimiter;

    public function __construct(
        ?AssistantService $assistantService = null,
        ?RateLimiterInterface $rateLimiter = null
    ) {
        $this->assistantService = $assistantService ?? new AssistantService();
        $this->rateLimiter = $rateLimiter ?? new FileRateLimiter();
    }

    /**
     * POST /assistant/chat
     * Read-only AI chat assistant endpoint for guest and authenticated users.
     */
    public function chat(Request $request): Response
    {
        // 1. Enforce MVP roles and determine rate limit identity
        $user = $request->getUser();
        $isAuthenticated = ($user !== null && !empty($user["id"]));

        if ($isAuthenticated) {
            $userRole = strtolower((string)($user["role"] ?? ""));
            if (!in_array($userRole, ["student", "developer"], true)) {
                return Response::error(
                    "Tính năng trợ lý AI hiện chỉ hỗ trợ sinh viên và khách vãng lai.",
                    Response::HTTP_FORBIDDEN,
                    ["error_code" => "FORBIDDEN_ROLE"]
                );
            }
            $throttleKey = "chat:auth:" . $user["id"];
            $maxAttempts = Config::chatAuthRateLimit();
        } else {
            $ip = $request->getClientIp();
            $throttleKey = "chat:guest:" . hash("sha256", $ip);
            $maxAttempts = Config::chatGuestRateLimit();
        }

        // 2. Atomic consume and rate limit check (exclusive per-key lock, fail closed)
        $decaySeconds = 60;
        $rateResult = $this->rateLimiter->consume($throttleKey, $maxAttempts, $decaySeconds);

        if (!$rateResult->allowed) {
            if ($rateResult->failedClosed) {
                return Response::error(
                    "Hệ thống kiểm soát tần suất đang gặp sự cố tạm thời. Vui lòng thử lại sau.",
                    Response::HTTP_SERVICE_UNAVAILABLE,
                    ["error_code" => "RATE_LIMIT_STORAGE_ERROR"]
                );
            }

            $safeSeconds = max(1, $rateResult->retryAfter);
            return Response::error(
                "Bạn đã gửi quá nhiều yêu cầu chat. Vui lòng thử lại sau ít phút.",
                Response::HTTP_TOO_MANY_REQUESTS,
                [
                    "rate_limit" => [
                        "Vượt quá giới hạn lượt tương tác. Vui lòng chờ {$safeSeconds} giây trước khi gửi tiếp."
                    ],
                    "error_code" => "RATE_LIMIT_EXCEEDED"
                ],
                ["Retry-After" => (string)$safeSeconds]
            );
        }

        // 4. Invoke Assistant Service
        try {
            $message = $request->input("message");
            $history = $request->input("history");

            $result = $this->assistantService->handleChat($message, $history);

            return Response::success($result, "Phản hồi từ trợ lý ảo.");
        } catch (ValidationException $e) {
            return Response::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->getErrors()
            );
        } catch (GeminiUnavailableException $e) {
            return Response::error(
                $e->getMessage(),
                Response::HTTP_SERVICE_UNAVAILABLE,
                ["error_code" => "SERVICE_UNAVAILABLE"]
            );
        } catch (GeminiTimeoutException $e) {
            return Response::error(
                $e->getMessage(),
                Response::HTTP_GATEWAY_TIMEOUT,
                ["error_code" => "TIMEOUT"]
            );
        } catch (GeminiRateLimitException $e) {
            return Response::error(
                $e->getMessage(),
                Response::HTTP_TOO_MANY_REQUESTS,
                ["error_code" => "PROVIDER_RATE_LIMIT"]
            );
        } catch (GeminiBlockedContentException $e) {
            return Response::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ["error_code" => "CONTENT_BLOCKED"]
            );
        } catch (GeminiException $e) {
            return Response::error(
                "Hệ thống trợ lý AI đang gặp sự cố tạm thời. Vui lòng thử lại sau.",
                Response::HTTP_SERVICE_UNAVAILABLE,
                ["error_code" => "ASSISTANT_ERROR"]
            );
        } catch (\Throwable $e) {
            return Response::error(
                "Đã có lỗi xảy ra trong quá trình xử lý yêu cầu chat.",
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ["error_code" => "INTERNAL_ERROR"]
            );
        }
    }
}
