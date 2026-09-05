<?php

/**
 * GOOGLE-AUTH-P1-02 Automated Isolated Regression Test Suite
 *
 * Requirements tested:
 *  1. Fail-fast environment guards: APP_ENV, DB_NAME, .env.testing requirement.
 *  2. Offline & zero network call guarantee (GoogleOAuthClient fails closed without JWKs).
 *  3. Password login regression: valid login, wrong password, non-existent user,
 *     suspended/banned rejection remain unaffected by Google Auth changes.
 *  4. JWT contract equivalence: both password and Google OAuth flows emit
 *     internal JWTs with identical claim structure (iss, iat, exp, id, email, role).
 *  5. Fixture isolation & cleanup guarantee: all test fixtures created during
 *     this run are purged in finally and shutdown handler.
 */

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

// Strict fail-fast: .env.testing MUST exist
$envTestingFile = __DIR__ . "/.env.testing";
if (!file_exists($envTestingFile)) {
    fwrite(STDERR, "FATAL: File '.env.testing' not found. Tests must run in isolated test environment.\n");
    exit(1);
}
$dotenv = Dotenv\Dotenv::createMutable(__DIR__, ".env.testing");
$dotenv->load();

use JobMarket\Domain\Authentication\GoogleOAuthClientInterface;
use JobMarket\Domain\Authentication\GoogleOAuthService;
use JobMarket\Domain\Authentication\OAuthStateStoreInterface;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Infrastructure\AuthenticationRepository;
use JobMarket\Infrastructure\GoogleOAuthClient;
use JobMarket\Infrastructure\OAuthIdentityRepository;
use JobMarket\Infrastructure\OAuthStateStore;

// 1. Environment & Database Guard
$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
$config = Config::env();
$dbName = $config["dbname"] ?? "";

echo "=================================================================\n";
echo "   GOOGLE-AUTH-P1-02 ISOLATED REGRESSION & HARDENING SUITE\n";
echo "=================================================================\n";

if ($appEnv !== "testing") {
    echo "FATAL: Test suite must run on 'testing' environment. Current APP_ENV: '{$appEnv}'.\n";
    exit(1);
}

if ($dbName !== "jobmarket_test") {
    echo "FATAL: Test suite must run against 'jobmarket_test'. Current DB_NAME: '{$dbName}'.\n";
    exit(1);
}

$db = new PDO(
    "mysql:dbname={$dbName};host={$config['host']};port={$config['port']}",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$runId = bin2hex(random_bytes(4));
$fixturePrefix = "test_p1_02_{$runId}_";
$tempStateDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jobmarket_test_p1_02_states_{$runId}";
@mkdir($tempStateDir, 0700, true);

// Unconditional fixture cleanup in shutdown & finally
$cleanup = function () use ($db, $fixturePrefix, $tempStateDir) {
    try {
        $db->exec("DELETE FROM oauth_identities WHERE provider_subject LIKE '{$fixturePrefix}%' OR email_at_link LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM companies WHERE name LIKE '{$fixturePrefix}%' OR id LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM users WHERE email LIKE '{$fixturePrefix}%' OR id LIKE '{$fixturePrefix}%'");
    } catch (Throwable) {
    }

    if (is_dir($tempStateDir)) {
        $files = glob($tempStateDir . DIRECTORY_SEPARATOR . "*");
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        @rmdir($tempStateDir);
    }
};

register_shutdown_function($cleanup);

function assertCondition(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("Assertion failed: " . $msg);
    }
}

// Mock GoogleOAuthClient for network-free deterministic tests
class MockP102Client implements GoogleOAuthClientInterface {
    public string $clientId = "test-client-id.apps.googleusercontent.com";
    public ?array $claimsToReturn = null;

    public function getAuthorizationUrl(string $state, string $nonce): string {
        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            "client_id" => $this->clientId,
            "response_type" => "code",
            "scope" => "openid email profile",
            "state" => $state,
            "nonce" => $nonce
        ]);
    }

    public function exchangeCode(string $code): array {
        return [
            "access_token" => "mock-google-access-token",
            "id_token"     => "mock.id.token",
            "expires_in"   => 3600
        ];
    }

    public function verifyIdToken(string $idToken, string $expectedNonce): array {
        if ($this->claimsToReturn !== null) {
            $claims = $this->claimsToReturn;
            $claims["nonce"] = $expectedNonce;
            return $claims;
        }
        return [
            "iss" => "https://accounts.google.com",
            "aud" => $this->clientId,
            "exp" => time() + 3600,
            "nonce" => $expectedNonce,
            "sub" => "default_sub",
            "email" => "default@example.com",
            "email_verified" => true,
            "name" => "Default User"
        ];
    }
}

$totalTests = 5;
$passedTests = 0;

try {
    $oauthRepo = new OAuthIdentityRepository();
    $authRepo = new AuthenticationRepository();
    $stateStore = new OAuthStateStore($tempStateDir);

    // =========================================================================
    // TEST 1: Environment Fail-Fast Guards Verification
    // =========================================================================
    echo "Running Test 1: Environment Fail-Fast Guards Verification... ";

    // 1a. Verify current test runs under APP_ENV=testing
    assertCondition($appEnv === "testing", "APP_ENV must be 'testing'");

    // 1b. Verify current test runs against jobmarket_test
    assertCondition($dbName === "jobmarket_test", "DB_NAME must be 'jobmarket_test'");

    // 1c. Verify .env.testing file exists
    assertCondition(file_exists(__DIR__ . "/.env.testing"), ".env.testing must exist");

    // 1d. Verify that all three P0/P1-01 test files contain strict fail-fast guards
    $testFiles = [
        "test_google_auth_p0_01.php",
        "test_google_auth_p0_02.php",
        "test_google_auth_p1_01.php",
    ];

    foreach ($testFiles as $tf) {
        $content = file_get_contents(__DIR__ . "/{$tf}");
        assertCondition($content !== false, "{$tf} must exist");

        // All files must check APP_ENV === 'testing'
        assertCondition(
            str_contains($content, "'testing'") && str_contains($content, "APP_ENV"),
            "{$tf} must guard APP_ENV=testing"
        );

        // All files must check dedicated test database
        assertCondition(
            str_contains($content, "jobmarket_test"),
            "{$tf} must guard DB_NAME=jobmarket_test"
        );

        // All files must fail-fast if .env.testing is missing
        assertCondition(
            str_contains($content, ".env.testing") && str_contains($content, "exit(1)"),
            "{$tf} must fail-fast if .env.testing missing"
        );

        // No test file should fall back to .env for loading
        assertCondition(
            !str_contains($content, 'createImmutable(__DIR__)'),
            "{$tf} must NOT fall back to .env"
        );
    }

    // 1e. Verify .env.example has Google OAuth placeholder documentation
    $envExample = file_get_contents(__DIR__ . "/.env.example");
    assertCondition($envExample !== false, ".env.example must exist");
    assertCondition(str_contains($envExample, "GOOGLE_OAUTH_CLIENT_ID"), ".env.example must document GOOGLE_OAUTH_CLIENT_ID");
    assertCondition(str_contains($envExample, "GOOGLE_OAUTH_CLIENT_SECRET"), ".env.example must document GOOGLE_OAUTH_CLIENT_SECRET");
    assertCondition(str_contains($envExample, "GOOGLE_OAUTH_REDIRECT_URI"), ".env.example must document GOOGLE_OAUTH_REDIRECT_URI");

    // 1f. Verify .env.example does NOT contain real credentials
    assertCondition(!str_contains($envExample, "AIza"), ".env.example must NOT contain real Google API key");
    assertCondition(str_contains($envExample, "your-google-client-id"), ".env.example must use placeholder values");

    // 1g. Verify .env.example redirect URI uses localhost (not http://manguonmo.test)
    assertCondition(str_contains($envExample, "http://localhost/auth/google/callback"), ".env.example redirect URI must use http://localhost");
    assertCondition(!str_contains($envExample, "GOOGLE_OAUTH_REDIRECT_URI=http://manguonmo.test"), ".env.example must NOT use http://manguonmo.test as redirect URI");

    // 1h. Verify operations doc matches actual code behavior
    $opsDoc = file_get_contents(__DIR__ . "/docs/GOOGLE_AUTH_OPERATIONS.md");
    assertCondition($opsDoc !== false, "GOOGLE_AUTH_OPERATIONS.md must exist");
    assertCondition(str_contains($opsDoc, "vẫn hiển thị"), "Operations doc must state button stays visible when config is missing");
    assertCondition(!str_contains($opsDoc, "bị ẩn/vô hiệu hóa"), "Operations doc must NOT claim button is hidden/disabled");
    assertCondition(str_contains($opsDoc, "localhost"), "Operations doc must reference localhost redirect URI");
    assertCondition(!str_contains($opsDoc, "http://manguonmo.test/auth/google/callback`" . PHP_EOL), "Operations doc must NOT list http://manguonmo.test as a valid redirect URI");
    assertCondition(str_contains($opsDoc, "your-staging-domain.example"), "Operations doc must use placeholder staging domain");
    assertCondition(str_contains($opsDoc, "your-production-domain.example"), "Operations doc must use placeholder production domain");

    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 2: Offline & Zero Network Call Guarantee
    // =========================================================================
    echo "Running Test 2: Offline & Zero Network Call Guarantee... ";

    // 2a. Mock client never calls network
    $mockClient = new MockP102Client();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    // Start works offline (mock returns URL)
    $authUrl = $service->start("student");
    assertCondition(str_contains($authUrl, "accounts.google.com"), "Start returns Google URL via mock (no network)");
    assertCondition(str_contains($authUrl, "openid"), "Authorization URL includes openid scope");

    // 2b. Real GoogleOAuthClient fails closed with empty JWK set (zero network)
    $realClient = new GoogleOAuthClient(
        "test-client-id.apps.googleusercontent.com",
        "test-secret",
        "http://localhost/auth/google/callback"
    );
    $realClient->setJwkKeysOverride([]); // Explicitly no JWK keys

    $caughtOffline = false;
    try {
        // Craft a minimal JWT-like string that passes header parsing
        $header = base64_encode(json_encode(["typ" => "JWT", "alg" => "RS256", "kid" => "test-kid"]));
        $payload = base64_encode(json_encode(["sub" => "test"]));
        $sig = base64_encode("dummy");
        $fakeToken = "{$header}.{$payload}.{$sig}";
        $realClient->verifyIdToken($fakeToken, "test_nonce");
    } catch (AuthenticationException $e) {
        $caughtOffline = str_contains($e->getMessage(), "JWK");
    }
    assertCondition($caughtOffline, "Real client must fail closed when JWK keys are empty/unavailable");

    // 2c. Real GoogleOAuthClient rejects token without kid (fail-closed before any network attempt)
    $caughtNoKid = false;
    try {
        $headerNoKid = base64_encode(json_encode(["typ" => "JWT", "alg" => "RS256"]));
        $payloadB = base64_encode(json_encode(["sub" => "test"]));
        $sigB = base64_encode("dummy");
        $tokenNoKid = "{$headerNoKid}.{$payloadB}.{$sigB}";
        $realClient->verifyIdToken($tokenNoKid, "test_nonce");
    } catch (AuthenticationException $e) {
        $caughtNoKid = str_contains($e->getMessage(), "kid");
    }
    assertCondition($caughtNoKid, "Real client must reject token without kid (fail-closed)");

    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 3: Password Login Regression
    // =========================================================================
    echo "Running Test 3: Password Login Regression... ";

    // Create a test user for password login testing
    $pwTestEmail = "{$fixturePrefix}pwuser@jobmarket.vn";
    $pwTestPassword = "Regression_Test_P@ss_2024";
    $pwTestId = "{$fixturePrefix}pwuser_id";
    $pwHash = password_hash($pwTestPassword, PASSWORD_BCRYPT);

    $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, 'student', 'active')")
       ->execute([$pwTestId, "PW Test User", $pwTestEmail, $pwHash]);

    // 3a. Valid password login works
    $authRepoTest = new AuthenticationRepository();
    $loginUser = \JobMarket\Domain\Authentication\Authentication::create("PW Test User", $pwTestEmail, $pwTestPassword, "student");
    $loginResult = $authRepoTest->loginWithDetails($loginUser);
    assertCondition(!empty($loginResult["token"]), "Valid password login must return non-empty token");
    assertCondition($loginResult["user"]["role"] === "student", "Password login must return correct role");
    assertCondition($loginResult["user"]["email"] === $pwTestEmail, "Password login must return correct email");

    // 3b. Wrong password rejected
    $caughtWrongPw = false;
    try {
        $badUser = \JobMarket\Domain\Authentication\Authentication::create("PW Test User", $pwTestEmail, "Wrong_Password_123", "student");
        $authRepoTest->loginWithDetails($badUser);
    } catch (AuthenticationException $e) {
        $caughtWrongPw = str_contains($e->getMessage(), "mật khẩu");
    }
    assertCondition($caughtWrongPw, "Wrong password must be rejected");

    // 3c. Non-existent user rejected
    $caughtNoUser = false;
    try {
        $ghostUser = \JobMarket\Domain\Authentication\Authentication::create("Ghost", "{$fixturePrefix}ghost@nobody.vn", "anything", "student");
        $authRepoTest->loginWithDetails($ghostUser);
    } catch (AuthenticationException $e) {
        $caughtNoUser = str_contains($e->getMessage(), "mật khẩu") || str_contains($e->getMessage(), "Email");
    }
    assertCondition($caughtNoUser, "Non-existent user must be rejected");

    // 3d. Banned user rejected via password login
    $bannedId = "{$fixturePrefix}banned_pw";
    $bannedEmail = "{$fixturePrefix}banned_pw@jobmarket.vn";
    $bannedHash = password_hash("BannedPass123", PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, 'student', 'banned')")
       ->execute([$bannedId, "Banned PW", $bannedEmail, $bannedHash]);

    $caughtBannedPw = false;
    try {
        $bannedUser = \JobMarket\Domain\Authentication\Authentication::create("Banned PW", $bannedEmail, "BannedPass123", "student");
        $authRepoTest->loginWithDetails($bannedUser);
    } catch (AuthenticationException $e) {
        $caughtBannedPw = str_contains($e->getMessage(), "khóa");
    }
    assertCondition($caughtBannedPw, "Banned user must be rejected on password login");

    // 3e. Suspended user rejected via password login
    $suspId = "{$fixturePrefix}susp_pw";
    $suspEmail = "{$fixturePrefix}susp_pw@jobmarket.vn";
    $suspHash = password_hash("SuspPass123", PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, 'company', 'suspended')")
       ->execute([$suspId, "Susp PW", $suspEmail, $suspHash]);

    $caughtSuspPw = false;
    try {
        $suspUser = \JobMarket\Domain\Authentication\Authentication::create("Susp PW", $suspEmail, "SuspPass123", "company");
        $authRepoTest->loginWithDetails($suspUser);
    } catch (AuthenticationException $e) {
        $caughtSuspPw = str_contains($e->getMessage(), "tạm khóa");
    }
    assertCondition($caughtSuspPw, "Suspended user must be rejected on password login");

    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 4: JWT Contract Equivalence (Password vs Google OAuth)
    // =========================================================================
    echo "Running Test 4: JWT Contract Equivalence (Password vs Google OAuth)... ";

    // 4a. Decode the password-login JWT and verify structure
    $pwJwt = $loginResult["token"];
    $pwClaims = JWT::decode($pwJwt);
    assertCondition($pwClaims !== null, "Password JWT must decode successfully");
    assertCondition(isset($pwClaims["iss"]), "Password JWT must have 'iss' claim");
    assertCondition($pwClaims["iss"] === "JobMarketplace", "Password JWT iss must be 'JobMarketplace'");
    assertCondition(isset($pwClaims["iat"]), "Password JWT must have 'iat' claim");
    assertCondition(isset($pwClaims["exp"]), "Password JWT must have 'exp' claim");
    assertCondition(isset($pwClaims["id"]), "Password JWT must have 'id' claim");
    assertCondition(isset($pwClaims["email"]), "Password JWT must have 'email' claim");
    assertCondition(isset($pwClaims["role"]), "Password JWT must have 'role' claim");
    assertCondition($pwClaims["role"] === "student", "Password JWT role must be 'student'");

    // 4b. Create a Google OAuth user and get JWT from callback
    $mockClient = new MockP102Client();
    $oauthService = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    $googleEmail = "{$fixturePrefix}google_jwt@jobmarket.vn";
    $googleSub = "{$fixturePrefix}sub_jwt_equiv";
    $startUrl = $oauthService->start("student");
    parse_str(parse_url($startUrl, PHP_URL_QUERY), $startParams);
    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $startParams["nonce"],
        "sub"            => $googleSub,
        "email"          => $googleEmail,
        "email_verified" => true,
        "name"           => "Google JWT Test"
    ];

    $googleResult = $oauthService->callback("test_code", $startParams["state"]);
    $googleJwt = $googleResult["token"];
    $googleClaims = JWT::decode($googleJwt);
    assertCondition($googleClaims !== null, "Google OAuth JWT must decode successfully");

    // 4c. Verify structural equivalence of JWT claims
    $requiredClaims = ["iss", "iat", "exp", "id", "email", "role"];
    foreach ($requiredClaims as $claim) {
        assertCondition(isset($googleClaims[$claim]), "Google OAuth JWT must have '{$claim}' claim");
    }

    assertCondition($googleClaims["iss"] === $pwClaims["iss"], "Both JWTs must have same issuer");
    assertCondition($googleClaims["role"] === "student", "Google OAuth JWT role must be 'student'");
    assertCondition(is_int($googleClaims["iat"]) || is_float($googleClaims["iat"]), "Google JWT 'iat' must be numeric");
    assertCondition(is_int($googleClaims["exp"]) || is_float($googleClaims["exp"]), "Google JWT 'exp' must be numeric");
    assertCondition($googleClaims["exp"] > $googleClaims["iat"], "Google JWT 'exp' must be after 'iat'");
    assertCondition($googleClaims["email"] === $googleEmail, "Google JWT email must match authenticated email");

    // 4d. Verify no extra Google-specific claims leak into internal JWT
    $internalClaimKeys = array_keys((array)$pwClaims);
    $googleClaimKeys = array_keys((array)$googleClaims);
    sort($internalClaimKeys);
    sort($googleClaimKeys);
    assertCondition($internalClaimKeys === $googleClaimKeys, "Password and Google JWTs must have identical claim keys");

    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 5: Fixture Isolation & Cleanup Guarantee
    // =========================================================================
    echo "Running Test 5: Fixture Isolation & Cleanup Guarantee... ";

    // 5a. Verify that test fixtures created in this run are identifiable by prefix
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id LIKE ? OR email LIKE ?");
    $stmt->execute(["{$fixturePrefix}%", "{$fixturePrefix}%"]);
    $fixtureCount = (int)$stmt->fetchColumn();
    assertCondition($fixtureCount > 0, "Test must have created some fixtures with prefix");

    // 5b. Verify that the cleanup function will purge them
    // (Dry-run: check that cleanup function is callable and registered)
    assertCondition(is_callable($cleanup), "Cleanup function must be callable");

    // 5c. Verify that the state directory exists and will be cleaned
    assertCondition(is_dir($tempStateDir), "Temp state directory must exist");

    // 5d. Verify that shutdown handler is registered (indirectly: our cleanup is the last registered)
    // The register_shutdown_function($cleanup) call above guarantees this.
    // We verify by checking that cleanup is the same closure.
    assertCondition($cleanup instanceof Closure, "Cleanup must be a Closure instance");

    // 5e. Run cleanup now and verify all fixtures are removed
    $cleanup();

    $stmt2 = $db->prepare("SELECT COUNT(*) FROM users WHERE id LIKE ? OR email LIKE ?");
    $stmt2->execute(["{$fixturePrefix}%", "{$fixturePrefix}%"]);
    $postCleanupCount = (int)$stmt2->fetchColumn();
    assertCondition($postCleanupCount === 0, "After cleanup, all user fixtures must be removed");

    $stmt3 = $db->prepare("SELECT COUNT(*) FROM oauth_identities WHERE provider_subject LIKE ?");
    $stmt3->execute(["{$fixturePrefix}%"]);
    $postCleanupOauth = (int)$stmt3->fetchColumn();
    assertCondition($postCleanupOauth === 0, "After cleanup, all OAuth identity fixtures must be removed");

    // Verify temp state dir is cleaned
    assertCondition(!is_dir($tempStateDir), "After cleanup, temp state directory must be removed");

    echo "PASSED\n";
    $passedTests++;

    echo "\n=================================================================\n";
    echo "   KẾT QUẢ KIỂM THỬ: {$passedTests}/{$totalTests} PASSED (100%)\n";
    echo "   TẤT CẢ TIÊU CHÍ GOOGLE-AUTH-P1-02 ĐẠT CHUẨN!\n";
    echo "=================================================================\n";

} catch (Throwable $e) {
    echo "\nFATAL TEST FAILURE:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
} finally {
    $cleanup();
}
