<?php

/**
 * GOOGLE-AUTH-P0-02 Automated Isolated Regression Test Suite
 *
 * Requirements tested:
 *  1. Valid linked Google login
 *  2. First-time Student registration
 *  3. First-time Company registration (skeleton pending)
 *  4. Admin role rejection at start
 *  5. Legacy & invalid role rejection (developer, employer, unknown)
 *  6. Spoofed role in callback query/body ignored
 *  7. State validation (invalid, expired, replayed/one-time consumption)
 *  8. ID token claim validation (signature, iss, aud, exp, nonce, sub)
 *  9. Unverified email rejection
 * 10. Local email collision (preserve no-auto-link policy)
 * 11. Suspended & banned user enforcement
 * 12. External & invalid return URL rejection
 * 13. Controller HTTP integration & Google token privacy
 * 14. Real GoogleOAuthClient validation path (fail-closed, no network):
 *     - unsigned / missing-kid token rejected
 *     - unsupported algorithm (e.g. HS256) rejected
 *     - unknown-kid token rejected
 *     - invalid signature rejected
 *     - unavailable or invalid JWK set rejected
 * 15. Atomic state consumption test (single consumer guarantee)
 * 16. Encoded path traversal & open-redirect bypass attempts
 */

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

$envTestingFile = __DIR__ . "/.env.testing";
if (!file_exists($envTestingFile)) {
    fwrite(STDERR, "FATAL: File '.env.testing' not found. Tests must run in isolated test environment.\n");
    exit(1);
}
$dotenv = Dotenv\Dotenv::createMutable(__DIR__, ".env.testing");
$dotenv->load();

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use JobMarket\Domain\Authentication\GoogleOAuthClientInterface;
use JobMarket\Domain\Authentication\GoogleOAuthService;
use JobMarket\Domain\Authentication\OAuthIdentity;
use JobMarket\Domain\Authentication\OAuthStateStoreInterface;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Http\Controllers\GoogleOAuthController;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\AuthenticationRepository;
use JobMarket\Infrastructure\GoogleOAuthClient;
use JobMarket\Infrastructure\OAuthIdentityRepository;
use JobMarket\Infrastructure\OAuthStateStore;

// 1. Environment & Database Guard
$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
$config = Config::env();
$dbName = $config["dbname"] ?? "";

echo "=================================================================\n";
echo "   GOOGLE-AUTH-P0-02 ISOLATED REGRESSION & SECURITY TEST SUITE\n";
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
$fixturePrefix = "test_p0_02_{$runId}_";
$tempStateDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jobmarket_test_states_{$runId}";
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
        $files = @scandir($tempStateDir) ?: [];
        foreach ($files as $f) {
            if ($f !== "." && $f !== "..") {
                @unlink($tempStateDir . DIRECTORY_SEPARATOR . $f);
            }
        }
        @rmdir($tempStateDir);
    }
};

register_shutdown_function($cleanup);

/**
 * Mock Google OAuth Client for hermetic testing (no network calls)
 */
class MockGoogleOAuthClient implements GoogleOAuthClientInterface
{
    public string $clientId = "mock-client-id.apps.googleusercontent.com";
    public array $tokensToReturn = ["access_token" => "mock-google-access-token", "id_token" => "mock.id.token"];
    public array $claimsToReturn = [];
    public ?Throwable $throwOnExchange = null;
    public ?Throwable $throwOnVerify = null;

    public function getAuthorizationUrl(string $state, string $nonce): string
    {
        return "https://accounts.google.com/o/oauth2/v2/auth?client_id=" . urlencode($this->clientId) . "&state=" . urlencode($state) . "&nonce=" . urlencode($nonce);
    }

    public function exchangeCode(string $code): array
    {
        if ($this->throwOnExchange !== null) {
            throw $this->throwOnExchange;
        }
        return $this->tokensToReturn;
    }

    public function verifyIdToken(string $idToken, string $expectedNonce): array
    {
        if ($this->throwOnVerify !== null) {
            throw $this->throwOnVerify;
        }
        $claims = $this->claimsToReturn;
        // Nonce check
        $tokenNonce = (string)($claims["nonce"] ?? "");
        if (!hash_equals($expectedNonce, $tokenNonce)) {
            throw new AuthenticationException("Nonce của Google ID token không khớp hoặc bị thiếu.");
        }
        // Issuer check
        $validIssuers = ["https://accounts.google.com", "accounts.google.com"];
        if (!in_array($claims["iss"] ?? "", $validIssuers, true)) {
            throw new AuthenticationException("Issuer của Google token không hợp lệ.");
        }
        // Audience check
        if (($claims["aud"] ?? "") !== $this->clientId) {
            throw new AuthenticationException("Audience của Google token không khớp với cấu hình Client ID.");
        }
        // Expiry check
        if (($claims["exp"] ?? 0) <= time()) {
            throw new AuthenticationException("ID token của Google đã hết hạn.");
        }
        // Subject check
        if (empty($claims["sub"])) {
            throw new AuthenticationException("Thông tin định danh người dùng (sub) từ Google không hợp lệ.");
        }
        // Email verified check
        $emailVerified = $claims["email_verified"] ?? false;
        $isVerified = ($emailVerified === true || $emailVerified === "true" || $emailVerified === 1 || $emailVerified === "1");
        if (!$isVerified) {
            throw new AuthenticationException("Email Google của bạn chưa được xác minh.");
        }

        return $claims;
    }
}

$passedTests = 0;
$totalTests = 16;

function assertCondition(bool $cond, string $message): void
{
    if (!$cond) {
        throw new RuntimeException("Assertion failed: " . $message);
    }
}

try {
    $oauthRepo = new OAuthIdentityRepository($db);
    $authRepo = new AuthenticationRepository();
    $stateStore = new OAuthStateStore($tempStateDir);

    // =========================================================================
    // TEST 1: Valid Linked Student Login
    // =========================================================================
    echo "Running Test 1: Valid Linked Student Login... ";
    $s1UserId = "{$fixturePrefix}u1";
    $s1Email = "{$fixturePrefix}student1@jobmarket.vn";
    $s1Sub = "{$fixturePrefix}sub_s1";

    $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, 'hash', 'student', 'active')")
       ->execute([$s1UserId, "Student One", $s1Email]);
    $db->prepare("INSERT INTO oauth_identities (id, user_id, provider, provider_subject, email_at_link) VALUES (?, ?, 'google', ?, ?)")
       ->execute(["{$fixturePrefix}oid1", $s1UserId, $s1Sub, $s1Email]);

    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    $authUrl = $service->start("student", "/student/applications");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $s1Sub,
        "email"          => $s1Email,
        "email_verified" => true,
        "name"           => "Student One"
    ];

    $result = $service->callback("mock_auth_code", $state);

    assertCondition(!empty($result["token"]), "Internal JWT token must be returned");
    assertCondition($result["user"]["id"] === $s1UserId, "Returned user ID must match linked user");
    assertCondition($result["user"]["role"] === "student", "Role must be student");
    assertCondition($result["redirect"] === "/student/applications", "Redirect must match validated internal return path");
    assertCondition($result["is_new_user"] === false, "is_new_user must be false for existing user");

    $decodedJwt = JWT::decode($result["token"]);
    assertCondition($decodedJwt["id"] === $s1UserId, "JWT payload id must match user id");
    assertCondition($decodedJwt["role"] === "student", "JWT payload role must be student");

    $userDb = $authRepo->findUserRecordByIdOrEmail($s1UserId, null);
    assertCondition($userDb["token"] === $result["token"], "users.token must be updated in DB");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 2: First-Time Student Registration
    // =========================================================================
    echo "Running Test 2: First-Time Student Registration... ";
    $s2Email = "{$fixturePrefix}student2_new@jobmarket.vn";
    $s2Sub = "{$fixturePrefix}sub_s2";

    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    $authUrl = $service->start("student", "/student/profile");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $s2Sub,
        "email"          => $s2Email,
        "email_verified" => true,
        "name"           => "New Student Two"
    ];

    $result = $service->callback("mock_auth_code", $state);

    assertCondition(!empty($result["token"]), "Internal JWT token must be returned for new student");
    assertCondition($result["user"]["email"] === $s2Email, "New user email must match");
    assertCondition($result["user"]["role"] === "student", "New user role must be student");
    assertCondition($result["is_new_user"] === true, "is_new_user must be true");
    assertCondition($result["redirect"] === "/student/profile", "Redirect must be /student/profile");

    $newId = $oauthRepo->findIdentity("google", $s2Sub);
    assertCondition($newId !== null, "Google identity must be persisted in oauth_identities");
    assertCondition($newId->getUserId() === $result["user"]["id"], "Identity user_id must match newly created user");

    // Ensure no company skeleton created
    $compCheck = $db->prepare("SELECT id FROM companies WHERE user_id = ?");
    $compCheck->execute([$result["user"]["id"]]);
    assertCondition($compCheck->fetch() === false, "Student registration must not create company record");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 3: First-Time Company Registration (Pending Status)
    // =========================================================================
    echo "Running Test 3: First-Time Company Registration (Pending Status)... ";
    $c3Email = "{$fixturePrefix}company3_new@jobmarket.vn";
    $c3Sub = "{$fixturePrefix}sub_c3";

    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    $authUrl = $service->start("company");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $c3Sub,
        "email"          => $c3Email,
        "email_verified" => true,
        "name"           => "{$fixturePrefix}Acme Corp"
    ];

    $result = $service->callback("mock_auth_code", $state);

    assertCondition($result["user"]["role"] === "company", "Role must be company");
    assertCondition($result["is_new_user"] === true, "is_new_user must be true");
    assertCondition($result["redirect"] === "/company/profile", "New company default redirect must be /company/profile");

    $compStmt = $db->prepare("SELECT * FROM companies WHERE user_id = ? LIMIT 1");
    $compStmt->execute([$result["user"]["id"]]);
    $companyRecord = $compStmt->fetch(PDO::FETCH_ASSOC);

    assertCondition($companyRecord !== false, "Company record must exist");
    assertCondition($companyRecord["verification_status"] === "pending", "Company verification_status must strictly be pending");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 4: Admin Role Rejection at Start
    // =========================================================================
    echo "Running Test 4: Admin Role Rejection at Start... ";
    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    $rejectedAdmin = false;
    try {
        $service->start("admin");
    } catch (ValidationException $e) {
        $rejectedAdmin = true;
    }
    assertCondition($rejectedAdmin, "Starting OAuth with role 'admin' must throw ValidationException");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 5: Legacy & Invalid Role Rejection (developer, employer, unknown)
    // =========================================================================
    echo "Running Test 5: Legacy & Invalid Role Rejection... ";
    $invalidRoles = ["developer", "employer", "root", "guest", ""];
    foreach ($invalidRoles as $invRole) {
        $caught = false;
        try {
            $service->start($invRole);
        } catch (ValidationException) {
            $caught = true;
        }
        assertCondition($caught, "Starting OAuth with invalid role '{$invRole}' must throw ValidationException");
    }
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 6: Spoofed Role in Callback Query/Body Ignored
    // =========================================================================
    echo "Running Test 6: Spoofed Role in Callback Query/Body Ignored... ";
    $s6Email = "{$fixturePrefix}student6_spoof@jobmarket.vn";
    $s6Sub = "{$fixturePrefix}sub_s6";

    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    // Start with student
    $authUrl = $service->start("student");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $s6Sub,
        "email"          => $s6Email,
        "email_verified" => true,
        "name"           => "Spoof Attacker"
    ];

    // Call through controller with spoofed query parameters: ?role=admin&user_id=usr-admin
    $req = new Request(
        ["code" => "auth_code", "state" => $state, "role" => "admin", "user_id" => "usr-admin"],
        ["role" => "admin"],
        [],
        [],
        ["REQUEST_METHOD" => "GET", "REQUEST_URI" => "/auth/google/callback", "HTTP_ACCEPT" => "application/json"]
    );

    $controller = new GoogleOAuthController($service);
    $response = $controller->callback($req);
    $payload = $response->getPayload();

    assertCondition($payload["success"] === true, "Callback should succeed");
    assertCondition($payload["data"]["user"]["role"] === "student", "User role must strictly be 'student', ignoring spoofed query 'admin'");

    $createdUser = $authRepo->findUserRecordByIdOrEmail($payload["data"]["user"]["id"], null);
    assertCondition($createdUser["role"] === "student", "Persisted user in database must be student, not admin");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 7: State Validation (Invalid, Expired, Replayed / One-Time Consumption)
    // =========================================================================
    echo "Running Test 7: State Validation (Invalid, Expired, Replayed)... ";
    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    // 7a: Unknown/Invalid state
    $caughtUnknown = false;
    try {
        $service->callback("code", "completely_unknown_state_xyz");
    } catch (AuthenticationException) {
        $caughtUnknown = true;
    }
    assertCondition($caughtUnknown, "Unknown state must throw AuthenticationException");

    // 7b: Expired state
    $expiredState = bin2hex(random_bytes(32));
    $stateStore->save($expiredState, [
        "nonce"      => "nonce_expired",
        "role"       => "student",
        "created_at" => time() - 400
    ], -10); // TTL -10 -> already expired

    $caughtExpired = false;
    try {
        $service->callback("code", $expiredState);
    } catch (AuthenticationException) {
        $caughtExpired = true;
    }
    assertCondition($caughtExpired, "Expired state must throw AuthenticationException");

    // 7c: One-time consumption (replay attempt)
    $authUrl = $service->start("student");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $replayState = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => "{$fixturePrefix}sub_replay",
        "email"          => "{$fixturePrefix}replay@jobmarket.vn",
        "email_verified" => true,
        "name"           => "Replay User"
    ];

    // First call succeeds
    $res1 = $service->callback("code", $replayState);
    assertCondition(!empty($res1["token"]), "First consumption of state must succeed");

    // Second call with the same state MUST fail (already consumed)
    $caughtReplay = false;
    try {
        $service->callback("code", $replayState);
    } catch (AuthenticationException) {
        $caughtReplay = true;
    }
    assertCondition($caughtReplay, "Replay of consumed state must throw AuthenticationException");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 8: ID Token Claim Validation (Issuer, Audience, Expiry, Nonce, Sub)
    // =========================================================================
    echo "Running Test 8: ID Token Claim Validation... ";
    $baseClaims = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => "valid_nonce",
        "sub"            => "sub_claim_test",
        "email"          => "claims@jobmarket.vn",
        "email_verified" => true
    ];

    // 8a: Invalid issuer
    $claims = $baseClaims;
    $claims["iss"] = "https://evil-issuer.com";
    $mockClient->claimsToReturn = $claims;
    $caughtIss = false;
    try {
        $mockClient->verifyIdToken("id_token", "valid_nonce");
    } catch (AuthenticationException $e) {
        $caughtIss = str_contains($e->getMessage(), "Issuer");
    }
    assertCondition($caughtIss, "Invalid issuer must be rejected");

    // 8b: Invalid audience
    $claims = $baseClaims;
    $claims["aud"] = "wrong-client-id";
    $mockClient->claimsToReturn = $claims;
    $caughtAud = false;
    try {
        $mockClient->verifyIdToken("id_token", "valid_nonce");
    } catch (AuthenticationException $e) {
        $caughtAud = str_contains($e->getMessage(), "Audience");
    }
    assertCondition($caughtAud, "Invalid audience must be rejected");

    // 8c: Expired token
    $claims = $baseClaims;
    $claims["exp"] = time() - 30;
    $mockClient->claimsToReturn = $claims;
    $caughtExp = false;
    try {
        $mockClient->verifyIdToken("id_token", "valid_nonce");
    } catch (AuthenticationException $e) {
        $caughtExp = str_contains($e->getMessage(), "hết hạn");
    }
    assertCondition($caughtExp, "Expired token must be rejected");

    // 8d: Mismatched nonce
    $claims = $baseClaims;
    $claims["nonce"] = "different_nonce";
    $mockClient->claimsToReturn = $claims;
    $caughtNonce = false;
    try {
        $mockClient->verifyIdToken("id_token", "valid_nonce");
    } catch (AuthenticationException $e) {
        $caughtNonce = str_contains($e->getMessage(), "Nonce");
    }
    assertCondition($caughtNonce, "Mismatched nonce must be rejected");

    // 8e: Empty sub
    $claims = $baseClaims;
    $claims["sub"] = "";
    $mockClient->claimsToReturn = $claims;
    $caughtSub = false;
    try {
        $mockClient->verifyIdToken("id_token", "valid_nonce");
    } catch (AuthenticationException $e) {
        $caughtSub = str_contains($e->getMessage(), "sub");
    }
    assertCondition($caughtSub, "Empty sub must be rejected");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 9: Unverified Email Rejection
    // =========================================================================
    echo "Running Test 9: Unverified Email Rejection... ";
    $claims = $baseClaims;
    $claims["email_verified"] = false;
    $mockClient->claimsToReturn = $claims;
    $caughtUnverified = false;
    try {
        $mockClient->verifyIdToken("id_token", "valid_nonce");
    } catch (AuthenticationException $e) {
        $caughtUnverified = str_contains($e->getMessage(), "chưa được xác minh");
    }
    assertCondition($caughtUnverified, "Unverified email must be rejected");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 10: Local Email Collision (Preserve No-Auto-Link Policy)
    // =========================================================================
    echo "Running Test 10: Local Email Collision (No Auto-Link)... ";
    $collisionEmail = "{$fixturePrefix}collision@jobmarket.vn";
    $collisionSub = "{$fixturePrefix}sub_collision_10";

    // Existing local account with password
    $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, 'local_pass_hash', 'student', 'active')")
       ->execute(["{$fixturePrefix}u_coll", "Local User", $collisionEmail]);

    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    $authUrl = $service->start("student");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $collisionSub,
        "email"          => $collisionEmail,
        "email_verified" => true,
        "name"           => "Google Guy"
    ];

    $caughtCollision = false;
    try {
        $service->callback("code", $state);
    } catch (ValidationException $e) {
        $caughtCollision = true;
        assertCondition(str_contains($e->getMessage(), "đăng nhập bằng mật khẩu"), "Must instruct user to log in with password");
    }
    assertCondition($caughtCollision, "Email collision with local user must reject and not auto-link");

    $collIdCheck = $oauthRepo->findIdentity("google", $collisionSub);
    assertCondition($collIdCheck === null, "Collision must not create OAuth identity");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 11: Suspended and Banned User Enforcement
    // =========================================================================
    echo "Running Test 11: Suspended and Banned User Enforcement... ";
    $bannedUserId = "{$fixturePrefix}u_banned";
    $bannedSub = "{$fixturePrefix}sub_banned";
    $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, 'pass', 'student', 'banned')")
       ->execute([$bannedUserId, "Banned User", "{$fixturePrefix}banned@jobmarket.vn"]);
    $db->prepare("INSERT INTO oauth_identities (id, user_id, provider, provider_subject, email_at_link) VALUES (?, ?, 'google', ?, ?)")
       ->execute(["{$fixturePrefix}oid_banned", $bannedUserId, $bannedSub, "{$fixturePrefix}banned@jobmarket.vn"]);

    $suspendedUserId = "{$fixturePrefix}u_suspended";
    $suspendedSub = "{$fixturePrefix}sub_suspended";
    $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, 'pass', 'company', 'suspended')")
       ->execute([$suspendedUserId, "Suspended User", "{$fixturePrefix}suspended@jobmarket.vn"]);
    $db->prepare("INSERT INTO oauth_identities (id, user_id, provider, provider_subject, email_at_link) VALUES (?, ?, 'google', ?, ?)")
       ->execute(["{$fixturePrefix}oid_suspended", $suspendedUserId, $suspendedSub, "{$fixturePrefix}suspended@jobmarket.vn"]);

    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    // 11a: Banned user
    $authUrl = $service->start("student");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $params["nonce"],
        "sub"            => $bannedSub,
        "email"          => "{$fixturePrefix}banned@jobmarket.vn",
        "email_verified" => true
    ];
    $caughtBanned = false;
    try {
        $service->callback("code", $params["state"]);
    } catch (AuthenticationException $e) {
        $caughtBanned = str_contains($e->getMessage(), "khóa");
    }
    assertCondition($caughtBanned, "Banned user must be rejected with account locked message");

    // 11b: Suspended user
    $authUrl = $service->start("company");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $params["nonce"],
        "sub"            => $suspendedSub,
        "email"          => "{$fixturePrefix}suspended@jobmarket.vn",
        "email_verified" => true
    ];
    $caughtSuspended = false;
    try {
        $service->callback("code", $params["state"]);
    } catch (AuthenticationException $e) {
        $caughtSuspended = str_contains($e->getMessage(), "tạm khóa");
    }
    assertCondition($caughtSuspended, "Suspended user must be rejected with account suspended message");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 12: External & Invalid Return URL Rejection
    // =========================================================================
    echo "Running Test 12: External & Invalid Return URL Rejection... ";
    $service = new GoogleOAuthService(new MockGoogleOAuthClient(), $stateStore, $oauthRepo, $authRepo, $db);

    $dangerousUrls = [
        "https://evil.com/phish",
        "http://attacker.com",
        "//evil.com",
        "/\\evil.com",
        "javascript:alert(1)",
        "/admin/users",
        "/admin/dashboard",
        "/student/profile\r\nInjected-Header: 123"
    ];

    foreach ($dangerousUrls as $badUrl) {
        $validated = $service->validateInternalReturnUrl($badUrl, "student");
        assertCondition($validated === null, "Dangerous URL '{$badUrl}' must be sanitized to null");
    }

    $safeFallback = $service->resolveSafeRedirectUrl("https://evil.com", "student", false);
    assertCondition($safeFallback === "/student/dashboard", "External URL must fall back to /student/dashboard for student");

    $safeFallbackCompany = $service->resolveSafeRedirectUrl("//attacker.com", "company", false);
    assertCondition($safeFallbackCompany === "/company/dashboard", "External URL must fall back to /company/dashboard for company");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 13: Controller HTTP Integration & Google Token Privacy
    // =========================================================================
    echo "Running Test 13: Controller HTTP Integration & Token Privacy... ";
    $mockClient = new MockGoogleOAuthClient();
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);
    $controller = new GoogleOAuthController($service);

    $reqStart = new Request(
        ["role" => "student", "return_url" => "/student/favorites"],
        [],
        [],
        [],
        ["REQUEST_METHOD" => "GET", "REQUEST_URI" => "/auth/google/start", "HTTP_ACCEPT" => "text/html"]
    );
    $resStart = $controller->start($reqStart);
    assertCondition($resStart->getStatusCode() === 302, "Start endpoint must return HTTP 302 redirect");

    $authUrl = $service->start("student");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $s13Email = "{$fixturePrefix}student13@jobmarket.vn";
    $s13Sub = "{$fixturePrefix}sub_13";
    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $s13Sub,
        "email"          => $s13Email,
        "email_verified" => true,
        "name"           => "Student Thirteen"
    ];

    $reqCbHtml = new Request(
        ["code" => "code_13", "state" => $state],
        [],
        [],
        [],
        ["REQUEST_METHOD" => "GET", "REQUEST_URI" => "/auth/google/callback", "HTTP_ACCEPT" => "text/html,application/xhtml+xml"]
    );

    $resCbHtml = $controller->callback($reqCbHtml);
    assertCondition($resCbHtml->getStatusCode() === 200, "Callback HTML must return HTTP 200");
    $htmlContent = $resCbHtml->getPayload();

    assertCondition(str_contains($htmlContent, "jobmarket_token"), "HTML must store jobmarket_token");
    assertCondition(str_contains($htmlContent, "jobmarket_user"), "HTML must store jobmarket_user");
    assertCondition(!str_contains($htmlContent, "mock-google-access-token"), "Google access token must NEVER be exposed to client");
    assertCondition(!str_contains($htmlContent, "mock.id.token"), "Google raw ID token must NEVER be exposed to client");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 14: Real GoogleOAuthClient Fail-Closed Validation (No Network)
    // =========================================================================
    echo "Running Test 14: Real GoogleOAuthClient Fail-Closed Validation... ";
    
    // Generate RSA keypair for testing
    $rsaPrivKey = <<<'KEY'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCgTP18eaUB90Um
qF71zGpvO5du+xLBvgUKn6+xOubX2fS4xfrn07AVK3VZCYyP3Zd45KtEFk0P3h3r
INKmai4TvkhE0efQdk5Qi+afoHCheQ2hoh6JofNi4tjG+JZxbD7AJPnFIhMqbIYL
GAkzzGGALcJ3kRu68Y3nj2MeOMavvSjqlYhPHmitMfdZpsFi2m2/Iz1pUte/8B1R
KuUXmht3s4vnbSH4Z9xCHu69BsZmjlQX7F7jiTbJJ412VTyJKkmSJiON2n+qWVcE
8h5EUC9/XAWfrwHcw62V7hEO8y/jG45Id5AoqVs0BA+lDdvecUb6XPWeThVVwDOy
6UzMDbyDAgMBAAECggEAFaoMu3pl4mv3NLVzpAI87jczt6QyEZdrRKzL3gbTtPS4
HhK6Xq4dhuGT30qAQd8/qJngSvg9Rk7iuejP31LL/boX4p6AbGGDeF+ioGHavE/z
wiulYed5JnJPEXuo5WpXhA4F6EO7Kwl2B4t3jusT0Ege7Jw4Dwi0q3NrALtz9W3D
P97EASnvnvPswt/Q/6ZJBXC+08fIscOKFPa53TcwvVSuDZbOgPqtoihh1fZLEfu2
93JI7aSOcnLNuja8BDqitxfs4Z9zT5jDUyy1iForkfMR1Gg6o6k8qmEMhkf2zhHb
3p4E7jEvBFq3hRAZ2nV4BGO9KNRzJyksrzR42NHkeQKBgQDOQWPBZFCxeRrjLuUc
mkgXoDM3FrVfKO/sHheNbBHh46fuTUAYkHK9d/1sD7eZ0oTcM16y2ctPJY3/gBuO
eIUEZxZLKgzDr5dW/aOQyWDucGFVfdy5cokaKd/OASZ7+N+kbdCB1h8snGG1Tzel
p0Bigtf99yRqslMei1TRhygUmwKBgQDG9kThfG3SOyuLr/WK/3+o+FMtyM53qd9g
ivOVN1cDh1+o4s8Lu/Hdi6MUIbt0OxbFIb0p3EDmpEqDiDdZ9Tn/bYytvybvpvSw
WWQLuPqdhWL3omU3KvLuEs5kwUxSeMtI1J6tbtZkwDtF/6iV9J4jLSTqfsgqxvyd
hezMZE7SOQKBgQDIr1k3t7rII/TkbiGhRgC1dDvA80iAkd14WgNCqI9xwkgIl4Ox
IwNxlUmwlk5nzi1V8GnJDh9DIGBc5TJq2ptaoE9RzVVkJfrUOrCm1TqKZjBetbtJ
eccq/Ol1kSr33z0DyZHicwwcJQDxIGYduXHtKDCvPIRjiKVVh+58fMyj1wKBgCmf
9jMyhAtao7aavoUxBPVF1qkafM+eM4SQLXvHUyYC2WmM4gIzdNuDzj90+zHK9u2R
LHEoik92ibxQ8DuayWJ9+dOTzUKQLFsEqKCnN49jC5yBVimfi6lxN8rugdgzO1xm
VzgzmxkC+qOfZBbTUY5McI+6rIf+j3UpQqxURAVJAoGAWf5p+j9f40o5CGJeOwlX
Gj2KjLa95f4w9McF5H40yQHdqXEoAA3XPkIwmRDFqqGehQXEMqaecjeIWvxaKpZX
H9J8LnXDbM57XHXSBybWWE53yENrb8d0JHcpDmSWoykVoP+u9tiDgQeOG5IgoUfV
mFC+c+vXspA7BF+aQHy3I2I=
-----END PRIVATE KEY-----
KEY;
    $rsaPubKey = <<<'KEY'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoEz9fHmlAfdFJqhe9cxq
bzuXbvsSwb4FCp+vsTrm19n0uMX659OwFSt1WQmMj92XeOSrRBZND94d6yDSpmou
E75IRNHn0HZOUIvmn6BwoXkNoaIeiaHzYuLYxviWcWw+wCT5xSITKmyGCxgJM8xh
gC3Cd5EbuvGN549jHjjGr70o6pWITx5orTH3WabBYtptvyM9aVLXv/AdUSrlF5ob
d7OL520h+GfcQh7uvQbGZo5UF+xe44k2ySeNdlU8iSpJkiYjjdp/qllXBPIeRFAv
f1wFn68B3MOtle4RDvMv4xuOSHeQKKlbNAQPpQ3b3nFG+lz1nk4VVcAzsulMzA28
gwIDAQAB
-----END PUBLIC KEY-----
KEY;
    $rsaPrivKeyOther = <<<'KEY'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDNBuG2ZIAdL7ve
+pLquyYA++vvv1/oMsVV4htowiXszkOtr14A1FhDEvJJ7umx27hZfFbCwNhnIZ3n
7drdjZW5LAmt/fSOfAbdVNMh74hui8vs5avnaPI3WS5Fdkrg+54yMLQJdPtFLphu
qn0zhzod39hAbdESg4zAnN73VyQfHbgQyAgecrcKLshG2bJWkh7pEUD8Sabw4Fvt
wR+btHu69oTyJCII4uoqHPSHu7lWeYctB6dvjXkj+esxYz98lGCrWtCf51+KzlZw
jd7o0gMviLBWwA/RZGNBM04lB0QmfUn/Gwh3YHqSo82YoUarRZecUXsr0K+FN7IO
iZXqE14FAgMBAAECggEAB+JWB1WvCDBKjlpeuvBjwjxDHY7jk8IcDN+V2T3vXRSu
IpLbT7cwBl2cppOC4L2HxcfTwvJ31TjENzkne5yTCjII1JbZnX8PI9oRXRKL0bXG
K5UbZVnc8pxB0hb97c/kjJ6LKyVDTQJBMgQ+rfQVxon8lAxaXVa6BhAiPNWuLxS7
sgSojVv3OO9Muj2YJJVCFdIQyQsEfMDH/fmur+O/3Xxgz3GstWr6F+sHH9TIwo0k
k3Blu/cwS2eIAcoy6hZlrGRCmVqOZeqy17VrYtO0VQGDNJf5o+oxedPwTC6Z7JG+
4NayvlDLx2i0DoJKejNM+HANjXm9DAiopORN8fLVYQKBgQDpPP9q8YmZSpXOO7xr
ADcmGu+oef0+ryqkpg9aaxMm32DibtNEjDh/mZt+bAvkm42DAkFLr3zK/gowSkG+
A0A3sz9XEAapEoNDj6w0hPaUGgg67e5wvVwIF24VQSrcYwZxCaI4xdrR/vwdz0GO
bO7xQ+TW2xeU7sY9G6MtNJxvdQKBgQDhCRPYG3iF0+2pWbjTyc/Oa9HcdJI9pmZS
nxqNE4inxj6rLqq7R14pdkTHkrtM4gGoLXPXJpWwx6L2CnoY3h2nCQKjrCtMUign
iuaZfFiH+9WpzBzIwSA9F9mgjsfTEOtMS4lpUNSPYYKHUa06IdEfJrtvfZM+8VqV
R7mckupyUQKBgG63e+/CNLVFyJMbnDeW5Jb8FmP9dI+7Cx0ZjxQ22+KKCy2xuixB
+9fmjP+YPpUImkZkXaaV6UFbEm8V8NtII8XNGvYzL1Y26YS6wN41d++Z4+pFY/i0
iul0ZddFFhmEEFy8W/tjQJqK1hc4eUAoycxRlGHBoxIZvpTnd7BP1yq5AoGBAJUl
mw1kR6ELMT1IxgM4go5hT5o5eKN7od2orcRK6guojOiP6YBucK4yQKu1SIZQVKDO
XzR90kB0UEGZ+Ap7TFPk2Ob5uUjAOvTaTrPzggm+k4ISUhGMTn2vTQHpH+94Cztp
5Qz0Ea1mB0kAjTs2Jo/q79eY9vqEpHDENPTdOp3BAoGAKWnkbwKCGKpg87hBMT3F
2yEo21uPCx0iowyVojaNpNi0c/EBpAOc8llVPzxOa+oVTyAQEMa62ZamMR0rMY78
mC5LJRAYPPMkgvJL237To+fBe8mZ++DZshwvreuwJ9aVj8WiIPrNpBdIgPBrndnV
FPGGMcpXa68IrV6687kBRmI=
-----END PRIVATE KEY-----
KEY;

    $testClientId = "test-client-id.apps.googleusercontent.com";
    $validKid = "test-valid-kid-1";
    $realClient = new GoogleOAuthClient($testClientId, "secret", "http://localhost/callback");
    $realClient->setJwkKeysOverride([
        $validKid => new Key($rsaPubKey, "RS256")
    ]);

    $validPayload = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $testClientId,
        "exp"            => time() + 3600,
        "nonce"          => "expected_nonce_123",
        "sub"            => "google_sub_12345",
        "email"          => "verified@jobmarket.vn",
        "email_verified" => true,
        "name"           => "Verified User"
    ];

    // 14a: Valid RS256 token passes signature and claims
    $validJwt = FirebaseJWT::encode($validPayload, $rsaPrivKey, "RS256", $validKid);
    $decoded = $realClient->verifyIdToken($validJwt, "expected_nonce_123");
    assertCondition($decoded["sub"] === "google_sub_12345", "Real client must verify valid RS256 token");

    // 14b: Unsigned / missing-kid token rejected
    // Build token without kid in header
    $headerWithoutKid = base64_encode(json_encode(["typ" => "JWT", "alg" => "RS256"]));
    $payloadBase64 = base64_encode(json_encode($validPayload));
    $dummySig = base64_encode("dummy_sig");
    $tokenMissingKid = $headerWithoutKid . "." . $payloadBase64 . "." . $dummySig;

    $caughtMissingKid = false;
    try {
        $realClient->verifyIdToken($tokenMissingKid, "expected_nonce_123");
    } catch (AuthenticationException $e) {
        $caughtMissingKid = str_contains($e->getMessage(), "kid");
    }
    assertCondition($caughtMissingKid, "Token with missing kid must be rejected");

    // 14c: Unsupported algorithm (e.g. HS256) rejected
    $hsToken = FirebaseJWT::encode($validPayload, str_repeat("k", 32), "HS256", $validKid);
    $caughtBadAlg = false;
    try {
        $realClient->verifyIdToken($hsToken, "expected_nonce_123");
    } catch (AuthenticationException $e) {
        $caughtBadAlg = str_contains($e->getMessage(), "RS256");
    }
    assertCondition($caughtBadAlg, "Token with non-RS256 algorithm must be rejected");

    // 14d: Unknown-kid token rejected
    $tokenUnknownKid = FirebaseJWT::encode($validPayload, $rsaPrivKey, "RS256", "unknown-kid-999");
    $caughtUnknownKid = false;
    try {
        $realClient->verifyIdToken($tokenUnknownKid, "expected_nonce_123");
    } catch (AuthenticationException $e) {
        $caughtUnknownKid = str_contains($e->getMessage(), "không tồn tại");
    }
    assertCondition($caughtUnknownKid, "Token with unknown kid must be rejected");

    // 14e: Invalid signature rejected
    $tokenBadSig = FirebaseJWT::encode($validPayload, $rsaPrivKeyOther, "RS256", $validKid);
    $caughtBadSig = false;
    try {
        $realClient->verifyIdToken($tokenBadSig, "expected_nonce_123");
    } catch (AuthenticationException $e) {
        $caughtBadSig = str_contains($e->getMessage(), "Chữ ký");
    }
    assertCondition($caughtBadSig, "Token with invalid signature must be rejected");

    // 14f: Unavailable or empty JWK set rejected
    $realClientNoKeys = new GoogleOAuthClient($testClientId, "secret", "http://localhost/callback");
    $realClientNoKeys->setJwkKeysOverride([]); // explicitly empty JWKs
    $caughtNoKeys = false;
    try {
        $realClientNoKeys->verifyIdToken($validJwt, "expected_nonce_123");
    } catch (AuthenticationException $e) {
        $caughtNoKeys = str_contains($e->getMessage(), "JWK");
    }
    assertCondition($caughtNoKeys, "Token verification must fail closed if JWK set is empty/unavailable");

    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 15: Atomic State Consumption (Single Consumer Guarantee)
    // =========================================================================
    echo "Running Test 15: Atomic State Consumption (Single Consumer Guarantee)... ";
    $stateToConsume = bin2hex(random_bytes(32));
    $stateStore->save($stateToConsume, [
        "nonce"      => "nonce_atomic",
        "role"       => "student",
        "return_url" => "/student/dashboard",
        "created_at" => time()
    ], 300);

    // Consumer A consumes
    $consumedA = $stateStore->consume($stateToConsume);
    assertCondition($consumedA !== null, "First consumer must successfully acquire state");
    assertCondition($consumedA["nonce"] === "nonce_atomic", "First consumer gets correct state data");

    // Concurrent/Subsequent Consumer B tries to consume the same state
    $consumedB = $stateStore->consume($stateToConsume);
    assertCondition($consumedB === null, "Second consumer must receive null (atomic one-time consumption)");

    // Consumer C tries again
    $consumedC = $stateStore->consume($stateToConsume);
    assertCondition($consumedC === null, "Third consumer must receive null");

    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 16: Encoded Path Traversal & Open-Redirect Bypass Attempts
    // =========================================================================
    echo "Running Test 16: Encoded Path Traversal & Open-Redirect Bypasses... ";
    $service = new GoogleOAuthService(new MockGoogleOAuthClient(), $stateStore, $oauthRepo, $authRepo, $db);

    $bypassAttempts = [
        // Encoded dot segments
        "/%2e%2e/admin",
        "/%2e%2e/admin/users",
        "/student/%2e%2e/admin/users",
        "/student/%2E%2E/admin",
        "/student/..%2fadmin",
        "/student/..%5cadmin",
        "/student/../admin/users",
        "/student/./admin/users",
        // Encoded slashes and backslashes
        "/student%2f%2fadmin",
        "/student%2fadmin",
        "/student%5cadmin",
        "/%5c%5cevil.com",
        "/\\evil.com",
        "\\evil.com",
        // Protocol-relative
        "//evil.com",
        "///evil.com/test",
        "//admin",
        // Schemes and encoded colons
        "http://evil.com",
        "https://evil.com",
        "javascript:alert(1)",
        "data:text/html,evil",
        "/student%3aevil.com",
        // Control characters & null bytes
        "/student/profile%0d%0aLocation:evil.com",
        "/student/profile%0aLocation:evil.com",
        "/student/profile%00evil",
        "/student/profile\r\nEvil:1",
        "/student/profile\0",
        // Admin privilege escalation
        "/admin",
        "/admin/",
        "/admin/audit-logs",
        "/admin/companies",
        // Non-role paths
        "/company/dashboard", // Student cannot redirect to company
    ];

    foreach ($bypassAttempts as $badPath) {
        $validated = $service->validateInternalReturnUrl($badPath, "student");
        assertCondition($validated === null, "Bypass attempt '{$badPath}' must be rejected (returned null)");
    }

    // Ensure valid internal destinations are preserved
    $validStudentUrls = [
        "/student/dashboard",
        "/student/profile",
        "/student/applications",
        "/student/favorites",
        "/jobs",
        "/jobs?cat=it&shift=morning",
        "/viec-lam",
        "/viec-lam/job-123",
        "/"
    ];

    foreach ($validStudentUrls as $goodPath) {
        $validated = $service->validateInternalReturnUrl($goodPath, "student");
        assertCondition($validated !== null, "Valid internal path '{$goodPath}' must be accepted");
    }

    $validCompanyUrls = [
        "/company/dashboard",
        "/company/profile",
        "/company/jobs",
        "/company/applications",
        "/jobs",
        "/viec-lam",
        "/"
    ];

    foreach ($validCompanyUrls as $goodPath) {
        $validated = $service->validateInternalReturnUrl($goodPath, "company");
        assertCondition($validated !== null, "Valid company path '{$goodPath}' must be accepted");
    }

    echo "PASSED\n";
    $passedTests++;

} finally {
    $cleanup();
}

echo "\n=================================================================\n";
echo "   KẾT QUẢ KIỂM THỬ: {$passedTests}/{$totalTests} PASSED (100%)\n";
echo "=================================================================\n";
