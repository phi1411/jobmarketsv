<?php

declare(strict_types=1);

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\Matching\Adapters\CandidateProfileAdapter;
use JobMarket\Domain\Matching\Adapters\JobRequirementsAdapter;
use JobMarket\Domain\Matching\Services\CanonicalHashService;
use JobMarket\Domain\Matching\Services\DeterministicMatcher;
use JobMarket\Domain\Matching\Services\Extraction\ExtractionValidator;
use JobMarket\Domain\Matching\Services\Extraction\GeminiExtractionAdapter;
use JobMarket\Domain\Matching\Services\MatchAnalysisService;
use JobMarket\Domain\Matching\Services\Normalizers\LocationNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\ScheduleNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\ApplicationRepository;
use JobMarket\Infrastructure\Gemini\GeminiClient;
use JobMarket\Infrastructure\JobMatchAnalysisRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Infrastructure\ProfileRepository;

use JobMarket\Domain\Assistant\RateLimiterInterface;
use JobMarket\Infrastructure\Security\FileRateLimiter;

class MatchAnalysisController extends Controller
{
    private const NO_CACHE_HEADERS = [
        'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ];

    private MatchAnalysisService $matchService;
    private RateLimiterInterface $rateLimiter;

    public function __construct(
        ?MatchAnalysisService $service = null,
        ?RateLimiterInterface $rateLimiter = null
    ) {
        $this->rateLimiter = $rateLimiter ?? new FileRateLimiter();

        if ($service !== null) {
            $this->matchService = $service;
        } else {
            $analysisRepo = new JobMatchAnalysisRepository();
            $appRepo = new ApplicationRepository();
            $profRepo = new ProfileRepository();
            $jobRepo = new JobRepository();

            $skillNormalizer = new SkillNormalizer();
            $scheduleNormalizer = new ScheduleNormalizer();
            $locationNormalizer = new LocationNormalizer();
            $redactor = new PiiRedactor();

            $candidateAdapter = new CandidateProfileAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, $redactor);
            $jobAdapter = new JobRequirementsAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, [], $redactor);
            $matcher = new DeterministicMatcher();
            $hashService = new CanonicalHashService();

            $extractionAdapter = null;
            if (Config::isGeminiEnabled()) {
                $geminiClient = new GeminiClient();
                $extractionAdapter = new GeminiExtractionAdapter($geminiClient, $redactor, new ExtractionValidator());
            }

            $this->matchService = new MatchAnalysisService(
                analysisRepository: $analysisRepo,
                applicationRepository: $appRepo,
                profileRepository: $profRepo,
                jobRepository: $jobRepo,
                candidateAdapter: $candidateAdapter,
                jobAdapter: $jobAdapter,
                matcher: $matcher,
                hashService: $hashService,
                extractionAdapter: $extractionAdapter,
                geminiModel: Config::geminiModel()
            );
        }
    }

    /**
     * POST /applications/{id}/match-analysis
     * Runs or retrieves cached match analysis for an application.
     */
    public function analyze(Request $request, string $id): Response
    {
        $user = $this->requireAuthenticatedStudent($request);

        // Ownership and consent are checked before the stateful limiter so an
        // IDOR attempt cannot consume or probe an analysis throttle bucket.
        $preflightState = $this->matchService->preflightAnalysis($user, $id);

        // Rate-limit per authenticated student & application for POST match-analysis.
        // Failed/timeout/invalid-response requests also consume an attempt.
        if ($preflightState === 'allowed') {
            $userId = (string)$user['id'];
            $throttleKey = "match:post:" . hash('sha256', $userId . ':' . $id);
            $maxAttempts = Config::aiMatchRateLimit();
            $decaySeconds = Config::aiMatchRateDecaySeconds();

            $rateResult = $this->rateLimiter->consume($throttleKey, $maxAttempts, $decaySeconds);
            if (!$rateResult->allowed) {
                if ($rateResult->failedClosed) {
                    return Response::error(
                        "Hệ thống kiểm soát tần suất đang gặp sự cố tạm thời. Vui lòng thử lại sau.",
                        Response::HTTP_SERVICE_UNAVAILABLE,
                        ["error_code" => "RATE_LIMIT_STORAGE_ERROR"],
                        self::NO_CACHE_HEADERS
                    );
                }

                $safeSeconds = max(1, $rateResult->retryAfter);
                $headers = array_merge(self::NO_CACHE_HEADERS, [
                    "Retry-After" => (string)$safeSeconds,
                    "X-RateLimit-Limit" => (string)$maxAttempts,
                    "X-RateLimit-Remaining" => (string)$rateResult->remaining,
                ]);

                return Response::error(
                    "Bạn đã gửi quá nhiều yêu cầu phân tích. Vui lòng thử lại sau.",
                    Response::HTTP_TOO_MANY_REQUESTS,
                    [
                        "rate_limit" => [
                            "Vượt quá giới hạn lượt phân tích. Vui lòng chờ {$safeSeconds} giây trước khi gửi tiếp."
                        ],
                        "error_code" => "RATE_LIMIT_EXCEEDED"
                    ],
                    $headers
                );
            }
        }

        $result = $this->matchService->analyze($user, $id);

        return new Response(
            [
                'success' => true,
                'message' => 'Kết quả phân tích độ phù hợp hồ sơ.',
                'data' => $result,
            ],
            Response::HTTP_OK,
            self::NO_CACHE_HEADERS
        );
    }

    /**
     * GET /applications/{id}/match-analysis
     * Read-only retrieval of current match analysis state.
     */
    public function show(Request $request, string $id): Response
    {
        $user = $this->requireAuthenticatedStudent($request);
        $result = $this->matchService->getAnalysis($user, $id);

        return new Response(
            [
                'success' => true,
                'message' => 'Trạng thái phân tích độ phù hợp.',
                'data' => $result,
            ],
            Response::HTTP_OK,
            self::NO_CACHE_HEADERS
        );
    }

    /**
     * PATCH /applications/{id}/match-consent
     * Revokes AI match analysis consent for an application.
     */
    public function updateConsent(Request $request, string $id): Response
    {
        $user = $this->requireAuthenticatedStudent($request);
        $body = $request->all();

        if (!array_key_exists('consent', $body)) {
            throw new ValidationException(
                ['consent' => 'Trường consent là bắt buộc.'],
                'Thiếu dữ liệu cập nhật quyền phân tích'
            );
        }

        $consent = $body['consent'];
        if ($consent !== false && $consent !== 0 && $consent !== '0' && $consent !== 'false') {
            throw new ValidationException(
                ['consent' => 'Chỉ hỗ trợ thu hồi quyền phân tích AI (consent: false).'],
                'Giá trị consent không hợp lệ'
            );
        }

        $result = $this->matchService->revokeConsent($user, $id);

        return new Response(
            [
                'success' => true,
                'message' => 'Thu hồi quyền phân tích AI thành công.',
                'data' => $result,
            ],
            Response::HTTP_OK,
            self::NO_CACHE_HEADERS
        );
    }

    /**
     * @return array{id: string, role: string}
     * @throws AuthenticationException|AuthorizationException
     */
    private function requireAuthenticatedStudent(Request $request): array
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để tiếp tục.");
        }

        $role = $user['role'] ?? null;
        if (!in_array($role, ['student', 'developer'], true)) {
            throw new AuthorizationException("Chức năng phân tích độ phù hợp chỉ dành cho sinh viên sở hữu đơn ứng tuyển.");
        }

        return $user;
    }
}
