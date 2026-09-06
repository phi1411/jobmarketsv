<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\Assistant\AssistantService;
use JobMarket\Domain\Assistant\Exceptions\GeminiBlockedContentException;
use JobMarket\Domain\Assistant\Exceptions\GeminiException;
use JobMarket\Domain\Assistant\Exceptions\GeminiRateLimitException;
use JobMarket\Domain\Assistant\Exceptions\GeminiTimeoutException;
use JobMarket\Domain\Assistant\Exceptions\GeminiUnavailableException;
use JobMarket\Domain\Assistant\RateLimiterInterface;
use JobMarket\Domain\Assistant\TelemetryService;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\Security\FileRateLimiter;

class AssistantController extends Controller
{
    private AssistantService $assistantService;
    private RateLimiterInterface $rateLimiter;
    private TelemetryService $telemetry;

    public function __construct(
        ?AssistantService $assistantService = null,
        ?RateLimiterInterface $rateLimiter = null,
        ?TelemetryService $telemetry = null
    ) {
        $this->assistantService = $assistantService ?? new AssistantService();
        $this->rateLimiter = $rateLimiter ?? new FileRateLimiter();
        $this->telemetry = $telemetry ?? new TelemetryService();
    }

    /**
     * POST /assistant/chat
     * Read-only AI chat assistant endpoint for guest and authenticated users.
     */
    public function chat(Request $request): Response
    {
        $startTime = microtime(true);

        // 1. Enforce MVP roles and determine rate limit identity
        $user = $request->getUser();
        $isAuthenticated = ($user !== null && !empty($user["id"]));

        if ($isAuthenticated) {
            $userRole = strtolower((string)($user["role"] ?? ""));
            if ($userRole === "admin") {
                return Response::error(
                    "Tính năng trợ lý AI hiện không khả dụng cho tài khoản quản trị viên.",
                    Response::HTTP_FORBIDDEN,
                    ["error_code" => "FORBIDDEN_ROLE"]
                );
            }

            if ($userRole === "company") {
                if (!Config::isGeminiCompanyEnabled()) {
                    return Response::error(
                        "Tính năng trợ lý AI dành cho doanh nghiệp hiện đang tạm tắt.",
                        Response::HTTP_FORBIDDEN,
                        ["error_code" => "FORBIDDEN_ROLE"]
                    );
                }
                $roleForService = "company";
            } elseif (in_array($userRole, ["student", "developer"], true)) {
                $roleForService = "student";
            } else {
                return Response::error(
                    "Tính năng trợ lý AI hiện chỉ hỗ trợ sinh viên, doanh nghiệp và khách vãng lai.",
                    Response::HTTP_FORBIDDEN,
                    ["error_code" => "FORBIDDEN_ROLE"]
                );
            }

            $throttleKey = "chat:auth:" . $user["id"];
            $maxAttempts = Config::chatAuthRateLimit();
        } else {
            $roleForService = "guest";
            $ip = $request->getClientIp();
            $throttleKey = "chat:guest:" . hash("sha256", $ip);
            $maxAttempts = Config::chatGuestRateLimit();
        }

        // 2. Atomic consume and rate limit check (exclusive per-key lock, fail closed)
        $decaySeconds = 60;
        $rateResult = $this->rateLimiter->consume($throttleKey, $maxAttempts, $decaySeconds);

        if (!$rateResult->allowed) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "rate_limit", $duration);

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

        // 3. Invoke Assistant Service
        try {
            $message = $request->input("message");
            $history = $request->input("history");

            $result = $this->assistantService->handleChat($message, $history, $roleForService);

            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "success", $duration);

            return Response::success($result, "Phản hồi từ trợ lý ảo.");
        } catch (ValidationException $e) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "validation_error", $duration);

            return Response::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->getErrors()
            );
        } catch (GeminiUnavailableException $e) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "service_unavailable", $duration);

            return Response::error(
                $e->getMessage(),
                Response::HTTP_SERVICE_UNAVAILABLE,
                ["error_code" => "SERVICE_UNAVAILABLE"]
            );
        } catch (GeminiTimeoutException $e) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "timeout", $duration);

            return Response::error(
                $e->getMessage(),
                Response::HTTP_GATEWAY_TIMEOUT,
                ["error_code" => "TIMEOUT"]
            );
        } catch (GeminiRateLimitException $e) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "provider_error", $duration);

            return Response::error(
                $e->getMessage(),
                Response::HTTP_TOO_MANY_REQUESTS,
                ["error_code" => "PROVIDER_RATE_LIMIT"]
            );
        } catch (GeminiBlockedContentException $e) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "safety_blocked", $duration);

            return Response::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ["error_code" => "CONTENT_BLOCKED"]
            );
        } catch (GeminiException $e) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "provider_error", $duration);

            return Response::error(
                "Hệ thống trợ lý AI đang gặp sự cố tạm thời. Vui lòng thử lại sau.",
                Response::HTTP_SERVICE_UNAVAILABLE,
                ["error_code" => "ASSISTANT_ERROR"]
            );
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->telemetry->recordRequest($roleForService, "provider_error", $duration);

            return Response::error(
                "Đã có lỗi xảy ra trong quá trình xử lý yêu cầu chat.",
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ["error_code" => "INTERNAL_ERROR"]
            );
        }
    }

    /**
     * POST /assistant/feedback
     * Record privacy-preserving thumbs-up / thumbs-down feedback.
     * Absolutely NO conversation text or user identity is accepted or saved.
     */
    public function feedback(Request $request): Response
    {
        // 1. Rate limiting on feedback to prevent spam
        $user = $request->getUser();
        if ($user !== null && !empty($user["id"])) {
            $throttleKey = "chat:feedback:" . $user["id"];
        } else {
            $ip = $request->getClientIp();
            $throttleKey = "chat:feedback:" . hash("sha256", $ip);
        }

        $rateResult = $this->rateLimiter->consume($throttleKey, 30, 60);
        if (!$rateResult->allowed) {
            return Response::error(
                "Bạn đã gửi quá nhiều lượt đánh giá. Vui lòng thử lại sau ít phút.",
                Response::HTTP_TOO_MANY_REQUESTS,
                ["error_code" => "RATE_LIMIT_EXCEEDED"]
            );
        }

        // 2. Validate rating parameter (strictly "up" or "down" only)
        $rating = $request->input("rating");
        if (!is_string($rating) || !in_array(strtolower(trim($rating)), ["up", "down"], true)) {
            return Response::error(
                "Đánh giá phản hồi không hợp lệ. Chỉ chấp nhận 'up' hoặc 'down'.",
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ["rating" => ["Giá trị đánh giá phải là 'up' hoặc 'down'."]]
            );
        }

        // 3. Increment aggregate counter only
        $this->telemetry->recordFeedback(strtolower(trim($rating)));

        return Response::success(null, "Cảm ơn bạn đã gửi phản hồi.");
    }
}
