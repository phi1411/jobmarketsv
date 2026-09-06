<?php

namespace JobMarket\Infrastructure\Gemini;

use JobMarket\Domain\Assistant\Exceptions\GeminiBlockedContentException;
use JobMarket\Domain\Assistant\Exceptions\GeminiException;
use JobMarket\Domain\Assistant\Exceptions\GeminiRateLimitException;
use JobMarket\Domain\Assistant\Exceptions\GeminiServiceException;
use JobMarket\Domain\Assistant\Exceptions\GeminiTimeoutException;
use JobMarket\Domain\Assistant\Exceptions\GeminiUnavailableException;
use JobMarket\Domain\Assistant\GeminiClientInterface;
use JobMarket\Domain\Assistant\GeminiResponse;
use JobMarket\Facades\Config;
use JobMarket\Support\Logger;

class GeminiClient implements GeminiClientInterface
{
    private const CONNECT_TIMEOUT_SECONDS = 5;
    private const MAX_RETRIES = 2;
    private const BASE_DELAY_MS = 200;

    private ?string $apiKey;
    private ?string $model;
    private int $timeoutSeconds;
    private bool $enabled;
    /** @var callable|null */
    private $curlRunner;

    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeoutSeconds = null,
        ?bool $enabled = null,
        ?callable $curlRunner = null
    ) {
        $this->apiKey = $apiKey ?? Config::geminiApiKey();
        $configuredModel = $model !== null ? $model : Config::geminiModel();
        $this->model = (!empty($configuredModel) && is_string($configuredModel)) ? trim($configuredModel) : null;
        $this->timeoutSeconds = ($timeoutSeconds !== null && $timeoutSeconds > 0)
            ? $timeoutSeconds
            : Config::geminiTimeout();
        $this->enabled = $enabled ?? Config::isGeminiEnabled();
        $this->curlRunner = $curlRunner;
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->apiKey) && !empty($this->model);
    }

    public function generateContent(array $contents, ?string $systemInstruction = null): GeminiResponse
    {
        if (!$this->isAvailable()) {
            throw new GeminiUnavailableException("Dịch vụ trợ lý AI hiện đang tắt hoặc chưa được cấu hình.");
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . rawurlencode($this->model) . ":generateContent";

        $body = [
            "contents" => $contents,
            "generationConfig" => [
                "temperature"     => 0.2,
                "maxOutputTokens" => 800,
                "topP"            => 0.95
            ],
            "safetySettings" => [
                [
                    "category"  => "HARM_CATEGORY_HARASSMENT",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ],
                [
                    "category"  => "HARM_CATEGORY_HATE_SPEECH",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ],
                [
                    "category"  => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ],
                [
                    "category"  => "HARM_CATEGORY_DANGEROUS_CONTENT",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ]
            ]
        ];

        if (!empty($systemInstruction)) {
            $body["systemInstruction"] = [
                "parts" => [
                    ["text" => $systemInstruction]
                ]
            ];
        }

        $jsonPayload = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            throw new GeminiServiceException("Không thể mã hóa dữ liệu gửi tới trợ lý AI.");
        }

        $headers = [
            "Content-Type: application/json",
            "x-goog-api-key: " . $this->apiKey
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt <= self::MAX_RETRIES) {
            $startTime = microtime(true);

            try {
                $response = $this->executeCurl($url, $headers, $jsonPayload);
                $duration = round((microtime(true) - $startTime) * 1000);

                Logger::info("Gemini provider response", [
                    "model"       => $this->model,
                    "status"      => $response["status"],
                    "duration_ms" => $duration,
                    "attempt"     => $attempt + 1
                ]);

                return $this->parseResponse($response);
            } catch (GeminiTimeoutException | GeminiRateLimitException | GeminiServiceException $e) {
                $lastException = $e;
                $isTransient = ($e instanceof GeminiTimeoutException)
                    || ($e instanceof GeminiRateLimitException)
                    || ($e->getCode() >= 500 && $e->getCode() <= 504)
                    || ($e->getCode() === 408);

                if (!$isTransient || $attempt >= self::MAX_RETRIES) {
                    throw $e;
                }

                $attempt++;
                $delayMs = min(1000, (int)(self::BASE_DELAY_MS * (2 ** ($attempt - 1)) + mt_rand(10, 50)));
                usleep($delayMs * 1000);
            } catch (GeminiException $e) {
                // Non-transient Gemini exceptions (e.g. Blocked content, 4xx) are never retried
                throw $e;
            }
        }

        throw $lastException ?? new GeminiServiceException("Không thể nhận phản hồi từ trợ lý AI sau nhiều lần thử.");
    }

    private function executeCurl(string $url, array $headers, string $payload): array
    {
        if ($this->curlRunner !== null) {
            return call_user_func($this->curlRunner, $url, $headers, $payload, $this->timeoutSeconds);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);

        return [
            "status" => $httpCode,
            "errno"  => $errno,
            "body"   => is_string($body) ? $body : ""
        ];
    }

    private function parseResponse(array $res): GeminiResponse
    {
        $status = $res["status"] ?? 0;
        $errno = $res["errno"] ?? 0;
        $body = $res["body"] ?? "";

        // 1. Check network / cURL level errors
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new GeminiTimeoutException("Quá thời gian kết nối tới trợ lý AI (Timeout).", 504);
        }
        if ($errno !== 0) {
            throw new GeminiServiceException("Không thể thiết lập kết nối tới trợ lý AI.", 503);
        }

        // 2. Check HTTP status code
        if ($status === 408) {
            throw new GeminiTimeoutException("Yêu cầu tới trợ lý AI bị quá thời gian.", 504);
        }
        if ($status === 429) {
            throw new GeminiRateLimitException("Hệ thống trợ lý AI đang vượt quá giới hạn lượt gọi. Vui lòng thử lại sau.", 429);
        }
        if ($status >= 500) {
            throw new GeminiServiceException("Dịch vụ trợ lý AI đang gặp sự cố tạm thời.", 503);
        }
        if ($status !== 200) {
            // For 4xx errors (e.g. 400, 401, 403), fail safely without revealing provider details
            throw new GeminiServiceException("Yêu cầu gửi tới trợ lý AI không hợp lệ.", $status);
        }

        // 3. Parse JSON body
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new GeminiServiceException("Phản hồi từ trợ lý AI không đúng định dạng.");
        }

        // 4. Check Safety Blocking at prompt level
        if (isset($decoded["promptFeedback"]["blockReason"])) {
            return new GeminiResponse(
                text: "Nội dung câu hỏi đã bị chặn do không phù hợp với tiêu chuẩn an toàn của hệ thống. Vui lòng đặt câu hỏi phù hợp với tìm việc làm part-time.",
                isBlocked: true,
                finishReason: "SAFETY",
                safetyNotice: "Nội dung bị chặn bởi bộ lọc an toàn của hệ thống."
            );
        }

        // 5. Check candidates
        $candidates = $decoded["candidates"] ?? [];
        if (empty($candidates) || !is_array($candidates)) {
            throw new GeminiServiceException("Không tìm thấy nội dung phản hồi từ trợ lý AI.");
        }

        $firstCandidate = $candidates[0];
        $finishReason = $firstCandidate["finishReason"] ?? null;

        if ($finishReason === "SAFETY") {
            return new GeminiResponse(
                text: "Câu trả lời đã bị chặn vì lý do an toàn nội dung. Vui lòng đặt câu hỏi khác.",
                isBlocked: true,
                finishReason: "SAFETY",
                safetyNotice: "Nội dung phản hồi bị chặn bởi bộ lọc an toàn."
            );
        }

        $parts = $firstCandidate["content"]["parts"] ?? [];
        if (empty($parts) || !is_array($parts)) {
            throw new GeminiServiceException("Dữ liệu phản hồi từ trợ lý AI bị rỗng.");
        }

        $text = "";
        foreach ($parts as $part) {
            if (isset($part["text"]) && is_string($part["text"])) {
                $text .= $part["text"];
            }
        }

        if (trim($text) === "") {
            throw new GeminiServiceException("Phản hồi văn bản từ trợ lý AI bị trống.");
        }

        return new GeminiResponse(
            text: trim($text),
            isBlocked: false,
            finishReason: $finishReason
        );
    }
}
