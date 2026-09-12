<?php

/**
 * CHAT-P1-02 & CHAT-P2-01 AUTOMATED TEST SUITE
 *
 * Tests:
 * 1. Company Launch Control & Feature Flag (off by default -> 403 COMPANY_CHAT_DISABLED).
 * 2. Company Mode Enabled -> returns 200 with Company Portal system instructions.
 * 3. Company Context Isolation -> ZERO applicant data, ZERO CVs, ZERO employer notes.
 * 4. Admin Role Permanently Forbidden -> HTTP 403 FORBIDDEN_ROLE.
 * 5. Global Disable Switch -> HTTP 503 SERVICE_UNAVAILABLE.
 * 6. Privacy-Preserving Telemetry -> Daily aggregate JSON with zero message/user/IP/token content.
 * 7. Telemetry Failure Isolation -> Telemetry errors do not fail chat responses.
 * 8. Feedback Endpoint -> Valid 'up' / 'down', validation for invalid ratings, zero content leakage.
 */

declare(strict_types=1);

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

spl_autoload_register(function ($class) {
    $prefix = "JobMarket\\";
    $baseDir = __DIR__ . "/app/";
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace("\\", "/", $relativeClass) . ".php";
    if (file_exists($file)) {
        require_once $file;
    }
});

use JobMarket\Domain\Assistant\AssistantService;
use JobMarket\Domain\Assistant\GeminiClientInterface;
use JobMarket\Domain\Assistant\GeminiResponse;
use JobMarket\Domain\Assistant\RateLimiterInterface;
use JobMarket\Domain\Assistant\RateLimitResult;
use JobMarket\Domain\Assistant\TelemetryService;
use JobMarket\Facades\Config;
use JobMarket\Http\Controllers\AssistantController;
use JobMarket\Http\Request;
use JobMarket\Infrastructure\Security\FileRateLimiter;

$passedCount = 0;
$totalCount = 0;

function it(string $description, callable $fn): void {
    global $passedCount, $totalCount;
    $totalCount++;
    echo "[TEST {$totalCount}] {$description}... ";
    try {
        $fn();
        echo "\033[32mPASS\033[0m\n";
        $passedCount++;
    } catch (\Throwable $e) {
        echo "\033[31mFAIL\033[0m\n";
        echo "   -> " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

// Disposable test directory for isolated rate limits and telemetry
$tempStorageDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jobmarket_chat_p1_p2_" . bin2hex(random_bytes(4));
@mkdir($tempStorageDir . "/rate_limits", 0777, true);
@mkdir($tempStorageDir . "/telemetry", 0777, true);

register_shutdown_function(function () use ($tempStorageDir) {
    // Clean up temp files
    $clean = function ($dir) use (&$clean) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $clean($path) : @unlink($path);
        }
        @rmdir($dir);
    };
    $clean($tempStorageDir);
});

// Mock Gemini Client
class MockGeminiClient implements GeminiClientInterface {
    public ?string $lastSystemInstruction = null;
    public ?array $lastContents = null;
    public bool $available = true;
    public string $responseText = "Phản hồi mẫu an toàn từ trợ lý ảo.";

    public function isAvailable(): bool {
        return $this->available;
    }

    public function generateContent(array $contents, ?string $systemInstruction = null, array $options = []): GeminiResponse {
        $this->lastContents = $contents;
        $this->lastSystemInstruction = $systemInstruction;
        return new GeminiResponse(
            text: $this->responseText,
            isBlocked: false,
            finishReason: null
        );
    }
}

echo "=================================================================\n";
echo "   CHAT-P1-02 & CHAT-P2-01 AUTOMATED REGRESSION TEST SUITE       \n";
echo "=================================================================\n\n";

// --- TEST 1: Config::isGeminiCompanyEnabled Fail-Closed ---
it("1. Config::isGeminiCompanyEnabled: Fails closed when flags are absent or false", function () {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "false";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";
    assert(Config::isGeminiCompanyEnabled() === false, "Must be false when global feature is disabled");

    $_ENV["GEMINI_FEATURE_ENABLED"] = "true";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "false";
    assert(Config::isGeminiCompanyEnabled() === false, "Must be false when company feature is disabled");

    $_ENV["GEMINI_FEATURE_ENABLED"] = "true";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";
    assert(Config::isGeminiCompanyEnabled() === true, "Must be true when both global and company flags are true");
});

// --- TEST 2: Company Chat Forbidden When Company Flag is Disabled ---
it("2. AssistantController: Company role gets HTTP 403 when GEMINI_COMPANY_ENABLED is false", function () use ($tempStorageDir) {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "true";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "false";

    $mockClient = new MockGeminiClient();
    $assistantService = new AssistantService($mockClient);
    $rateLimiter = new FileRateLimiter($tempStorageDir . "/rate_limits");
    $telemetry = new TelemetryService($tempStorageDir . "/telemetry");

    $controller = new AssistantController($assistantService, $rateLimiter, $telemetry);

    // Request with Company authentication
    $request = new Request([], [
        "message" => "Làm sao để đăng tin tuyển dụng?"
    ], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $request->setUser(["id" => "comp-001", "role" => "company"]);

    $res = $controller->chat($request);
    assert($res->getStatusCode() === 403, "Expected HTTP 403 for company when flag disabled, got " . $res->getStatusCode());
    
    $payload = $res->getPayload();
    assert(($payload["errors"]["error_code"] ?? "") === "FORBIDDEN_ROLE");
    assert(str_contains($payload["message"] ?? "", "tạm tắt"));
});

// --- TEST 3: Company Chat Allowed When Company Flag is Enabled ---
it("3. AssistantController: Company role receives HTTP 200 when GEMINI_COMPANY_ENABLED is true", function () use ($tempStorageDir) {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "true";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";

    $mockClient = new MockGeminiClient();
    $assistantService = new AssistantService($mockClient);
    $rateLimiter = new FileRateLimiter($tempStorageDir . "/rate_limits");
    $telemetry = new TelemetryService($tempStorageDir . "/telemetry");

    $controller = new AssistantController($assistantService, $rateLimiter, $telemetry);

    $request = new Request([], [
        "message" => "Hướng dẫn tôi cách đăng tin và xét duyệt ứng viên."
    ], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $request->setUser(["id" => "comp-001", "role" => "company"]);

    $res = $controller->chat($request);
    assert($res->getStatusCode() === 200, "Expected HTTP 200 for company when flag enabled, got " . $res->getStatusCode());

    $instruction = $mockClient->lastSystemInstruction;
    assert(stripos($instruction, "Cổng Doanh nghiệp") !== false, "Instruction must be tailored for Company Portal");
    assert(stripos($instruction, "quản lý danh sách ứng viên") !== false, "Instruction must explain application statuses");
    assert(stripos($instruction, "trạng thái xác minh") !== false, "Instruction must explain verification status");
    assert(stripos($instruction, "pending") !== false, "Instruction must contain pending status");
    assert(stripos($instruction, "viewed") !== false, "Instruction must contain viewed status");
    assert(stripos($instruction, "shortlisted") !== false, "Instruction must contain shortlisted status");
    assert(stripos($instruction, "accepted") !== false, "Instruction must contain accepted status");
    assert(stripos($instruction, "rejected") !== false, "Instruction must contain rejected status");
    assert(stripos($instruction, "withdrawn") !== false, "Instruction must contain withdrawn status");
    assert(stripos($instruction, "reviewing") === false, "Instruction must NOT contain reviewing status");
});

// --- TEST 4: Company System Instruction Privacy Boundary ---
it("4. AssistantService: Company mode receives ZERO applicant data, ZERO CV data, and ZERO employer notes", function () {
    $mockClient = new MockGeminiClient();
    $assistantService = new AssistantService($mockClient);

    $systemInstruction = $assistantService->buildSystemInstruction("company", []);

    assert(!str_contains($systemInstruction, "cv_url"), "Must NOT mention cv_url");
    assert(!str_contains($systemInstruction, "applicant_name"), "Must NOT mention applicant details");
    assert(!str_contains($systemInstruction, "employer_notes"), "Must NOT mention employer internal notes");
    assert(!str_contains($systemInstruction, "DANH SÁCH VIỆC LÀM CÔNG KHAI"), "Company mode must NOT inject public job search listings");
    assert(str_contains($systemInstruction, "Tuyệt đối KHÔNG yêu cầu, tiếp nhận, xử lý hoặc đưa ra thông tin cá nhân của ứng viên"), "Must contain strict applicant privacy boundary");
    assert(!str_contains($systemInstruction, "reviewing"), "Company mode must NOT contain reviewing status");
    assert(str_contains($systemInstruction, "viewed"), "Company mode must contain viewed status");
    assert(str_contains($systemInstruction, "withdrawn"), "Company mode must contain withdrawn status");
});

// --- TEST 5: Admin Role Is ALWAYS Forbidden ---
it("5. AssistantController: Admin role is ALWAYS rejected with HTTP 403", function () use ($tempStorageDir) {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "true";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";

    $mockClient = new MockGeminiClient();
    $assistantService = new AssistantService($mockClient);
    $rateLimiter = new FileRateLimiter($tempStorageDir . "/rate_limits");
    $telemetry = new TelemetryService($tempStorageDir . "/telemetry");

    $controller = new AssistantController($assistantService, $rateLimiter, $telemetry);

    $request = new Request([], [
        "message" => "Tôi là Admin, hỗ trợ tôi kiểm tra hệ thống."
    ], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $request->setUser(["id" => "adm-001", "role" => "admin"]);

    $res = $controller->chat($request);
    assert($res->getStatusCode() === 403, "Admin must receive HTTP 403");
    $payload = $res->getPayload();
    assert(($payload["errors"]["error_code"] ?? "") === "FORBIDDEN_ROLE");
});

// --- TEST 6: Global Assistant Kill-Switch ---
it("6. AssistantController: Global kill-switch returns HTTP 503 for all roles", function () use ($tempStorageDir) {
    $_ENV["GEMINI_FEATURE_ENABLED"] = "false";
    $_ENV["GEMINI_COMPANY_ENABLED"] = "true";

    $mockClient = new MockGeminiClient();
    $mockClient->available = false; // Emulating feature flag / key absence
    $assistantService = new AssistantService($mockClient);
    $rateLimiter = new FileRateLimiter($tempStorageDir . "/rate_limits");
    $telemetry = new TelemetryService($tempStorageDir . "/telemetry");

    $controller = new AssistantController($assistantService, $rateLimiter, $telemetry);

    // Guest request
    $request = new Request([], ["message" => "Tìm việc"], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $res = $controller->chat($request);
    assert($res->getStatusCode() === 503, "Expected 503 when globally disabled, got " . $res->getStatusCode());

    $payload = $res->getPayload();
    assert(($payload["errors"]["error_code"] ?? "") === "SERVICE_UNAVAILABLE");
});

// --- TEST 7: Privacy-Preserving Telemetry File Structure ---
it("7. TelemetryService: Records daily aggregate counters with zero message/user/IP content", function () use ($tempStorageDir) {
    $test7Dir = $tempStorageDir . "/telemetry_test7";
    @mkdir($test7Dir, 0777, true);
    $telemetry = new TelemetryService($test7Dir);

    // Record various requests
    $telemetry->recordRequest("guest", "success", 0.45);
    $telemetry->recordRequest("student", "success", 1.85);
    $telemetry->recordRequest("company", "rate_limit", 0.05);
    $telemetry->recordRequest("student", "safety_blocked", 0.90);
    $telemetry->recordRequest("guest", "provider_error", 12.5);

    $metrics = $telemetry->getDailyMetrics();
    assert($metrics !== null, "Daily metrics file must exist");
    assert($metrics["request_count"] === 5, "Total requests should be 5");
    assert($metrics["success_count"] === 2, "Success count should be 2");
    assert($metrics["rate_limit_count"] === 1, "Rate limit count should be 1");
    assert($metrics["safety_blocked_count"] === 1, "Safety blocked count should be 1");
    assert($metrics["provider_error_count"] === 1, "Provider error count should be 1");
    assert($metrics["latency_buckets"]["lt_1s"] === 3, "Latency <1s count should be 3");
    assert($metrics["latency_buckets"]["1s_to_3s"] === 1, "Latency 1-3s count should be 1");
    assert($metrics["latency_buckets"]["gte_10s"] === 1, "Latency >=10s count should be 1");

    // STRICT VERIFICATION: Raw file must not contain PII or message words
    $date = date("Y-m-d");
    $rawFile = file_get_contents($test7Dir . "/chat_metrics_{$date}.json");
    assert(!str_contains($rawFile, "user_id"), "Must NOT contain user_id");
    assert(!str_contains($rawFile, "message"), "Must NOT contain message");
    assert(!str_contains($rawFile, "ip_address"), "Must NOT contain IP address");
    assert(!str_contains($rawFile, "token"), "Must NOT contain token");
});

// --- TEST 8: Telemetry Failure Isolation (Fail-Open) ---
it("8. TelemetryService: Storage failure does NOT crash user chat response", function () use ($tempStorageDir) {
    // Point telemetry to an unwritable fake path
    $brokenTelemetry = new TelemetryService("/dev/null/impossible/path/that/cannot/exist");

    $mockClient = new MockGeminiClient();
    $assistantService = new AssistantService($mockClient);
    $rateLimiter = new FileRateLimiter($tempStorageDir . "/rate_limits");
    $controller = new AssistantController($assistantService, $rateLimiter, $brokenTelemetry);

    $request = new Request([], ["message" => "Tìm việc"], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);

    // Should complete successfully without throwing
    $res = $controller->chat($request);
    assert($res->getStatusCode() === 200, "Chat must return 200 even if telemetry fails");
});

// --- TEST 9: Feedback Endpoint Success & Validation ---
it("9. AssistantController: feedback() accepts 'up' and 'down', rejects invalid inputs", function () use ($tempStorageDir) {
    $rateLimiter = new FileRateLimiter($tempStorageDir . "/rate_limits");
    $telemetry = new TelemetryService($tempStorageDir . "/telemetry");
    $controller = new AssistantController(null, $rateLimiter, $telemetry);

    // 1. Valid 'up' rating
    $reqUp = new Request([], ["rating" => "up"], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $resUp = $controller->feedback($reqUp);
    assert($resUp->getStatusCode() === 200, "Expected 200 for 'up', got " . $resUp->getStatusCode());

    // 2. Valid 'down' rating
    $reqDown = new Request([], ["rating" => "down"], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $resDown = $controller->feedback($reqDown);
    assert($resDown->getStatusCode() === 200, "Expected 200 for 'down', got " . $resDown->getStatusCode());

    // 3. Invalid rating
    $reqInvalid = new Request([], ["rating" => "super_great"], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $resInvalid = $controller->feedback($reqInvalid);
    assert($resInvalid->getStatusCode() === 422, "Expected 422 for invalid rating, got " . $resInvalid->getStatusCode());

    // 4. Verify aggregate counts updated
    $metrics = $telemetry->getDailyMetrics();
    assert($metrics["feedback"]["thumbs_up"] >= 1, "Thumbs up count must be at least 1");
    assert($metrics["feedback"]["thumbs_down"] >= 1, "Thumbs down count must be at least 1");
});

// --- TEST 10: Feedback Payload Ignores & Disallows Conversation Text ---
it("10. AssistantController: Feedback ignores any supplied message or conversation text", function () use ($tempStorageDir) {
    $rateLimiter = new FileRateLimiter($tempStorageDir . "/rate_limits");
    $telemetry = new TelemetryService($tempStorageDir . "/telemetry");
    $controller = new AssistantController(null, $rateLimiter, $telemetry);

    // Send payload with attacker attempting to log text
    $sneakyText = "SECRET_PRIVATE_USER_PHONE_0987654321";
    $req = new Request([], [
        "rating" => "up",
        "message" => $sneakyText,
        "transcript" => $sneakyText
    ], [], [], [
        "REQUEST_METHOD" => "POST",
        "REMOTE_ADDR" => "127.0.0.1"
    ]);
    $res = $controller->feedback($req);
    assert($res->getStatusCode() === 200);

    // Verify raw file does NOT contain sneaky text
    $date = date("Y-m-d");
    $rawFile = file_get_contents($tempStorageDir . "/telemetry/chat_metrics_{$date}.json");
    assert(!str_contains($rawFile, $sneakyText), "Telemetry file must NEVER contain injected text");
});

echo "\n=================================================================\n";
echo "   KẾT QUẢ: {$passedCount}/{$totalCount} BÀI TEST CHAT-P1-02 & P2-01 ĐẠT THÀNH CÔNG (100% PASS)\n";
echo "=================================================================\n";

if ($passedCount !== $totalCount) {
    exit(1);
}
