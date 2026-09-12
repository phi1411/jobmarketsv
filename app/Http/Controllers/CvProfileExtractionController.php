<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\Assistant\Exceptions\GeminiBlockedContentException;
use JobMarket\Domain\Assistant\Exceptions\GeminiException;
use JobMarket\Domain\Assistant\Exceptions\GeminiRateLimitException;
use JobMarket\Domain\Assistant\Exceptions\GeminiTimeoutException;
use JobMarket\Domain\Cv\CvProfileExtractionService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Facades\Config;
use JobMarket\Http\Middlewares\RoleMiddleware;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\Security\FileRateLimiter;

class CvProfileExtractionController extends Controller
{
    public function __construct(
        private ?CvProfileExtractionService $service = null,
        private ?FileRateLimiter $rateLimiter = null
    ) {
        $this->service ??= new CvProfileExtractionService();
        $this->rateLimiter ??= new FileRateLimiter();
    }

    public function analyze(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để phân tích CV.");
        }
        RoleMiddleware::check($request, ["student", "developer"]);

        $rate = $this->rateLimiter->consume("cv:analyze:" . hash("sha256", (string)$user["id"]), 5, 3600);
        if (!$rate->allowed) {
            return Response::error(
                "Bạn đã phân tích CV quá nhiều lần. Vui lòng thử lại sau.",
                Response::HTTP_TOO_MANY_REQUESTS,
                ["retry_after" => [$rate->retryAfter]]
            );
        }

        try {
            return Response::success(
                $this->service->analyze($user),
                "Gemini đã đọc CV. Vui lòng kiểm tra trước khi điền vào hồ sơ."
            );
        } catch (GeminiTimeoutException) {
            return Response::error("Gemini xử lý CV quá thời gian. Vui lòng thử lại.", Response::HTTP_GATEWAY_TIMEOUT);
        } catch (GeminiRateLimitException) {
            return Response::error("Gemini đang quá tải. Vui lòng thử lại sau.", Response::HTTP_TOO_MANY_REQUESTS);
        } catch (GeminiBlockedContentException) {
            return Response::error("CV không thể được Gemini xử lý an toàn.", Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (GeminiException) {
            return Response::error("Gemini chưa thể phân tích CV lúc này.", Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
