<?php

/**
 * GOOGLE-AUTH-P1-01 Automated Isolated Regression Test Suite
 *
 * Requirements tested:
 *  1. Login page markup: divider "hoặc", Google button, no role parameter, accessible label, SVG icon.
 *  2. Register page markup: Google button below role selector, role-bound, divider, accessible label, SVG icon.
 *  3. Absence of admin option in role selector, Google button, or scripts.
 *  4. Password forms preservation: all input fields, labels, buttons intact on both pages.
 *  5. CSS styles verification: auth-divider, btn-google, focus, disabled states exist in style.css.
 *  6. Anti-XSS query string error handling in Login and Register views (textContent usage).
 *  7. Backend Start without role: permitted for linked users, stores null role in state.
 *  8. Backend Callback for linked student without role in state: logs in successfully with student JWT.
 *  9. Backend Callback for linked company without role in state: logs in successfully with company JWT.
 * 10. Backend Callback for first-time user without role in state: fails closed, instructs user to register.
 * 11. Backend Start with invalid role (admin, employer, root): strictly rejected with ValidationException.
 * 12. Controller HTTP integration for start (no role, student, company, admin).
 */

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

$envTestingFile = __DIR__ . "/.env.testing";
if (file_exists($envTestingFile)) {
    $dotenv = Dotenv\Dotenv::createMutable(__DIR__, ".env.testing");
    $dotenv->load();
} else {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use JobMarket\Domain\Authentication\GoogleOAuthClientInterface;
use JobMarket\Domain\Authentication\GoogleOAuthService;
use JobMarket\Domain\Authentication\OAuthStateStoreInterface;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Http\Controllers\GoogleOAuthController;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\AuthenticationRepository;
use JobMarket\Infrastructure\OAuthIdentityRepository;
use JobMarket\Infrastructure\OAuthStateStore;

// 1. Environment & Database Guard
$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
$config = Config::env();
$dbName = $config["dbname"] ?? "";

echo "=================================================================\n";
echo "   GOOGLE-AUTH-P1-01 ISOLATED REGRESSION & UI CONTRACT SUITE\n";
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
$fixturePrefix = "test_p1_01_{$runId}_";
$tempStateDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jobmarket_test_p1_01_states_{$runId}";
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
class MockP1Client implements GoogleOAuthClientInterface {
    public string $clientId = "mock-p1-client-id.apps.googleusercontent.com";
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

$passedTests = 0;

try {
    // =========================================================================
    // TEST 1: Login Page Markup & Contracts
    // =========================================================================
    echo "Running Test 1: Login Page Markup & Contracts... ";
    $loginContent = file_get_contents(__DIR__ . "/app/Views/auth/login.php");
    assertCondition($loginContent !== false, "login.php must exist");

    // Divider check
    assertCondition(str_contains($loginContent, 'class="auth-divider"'), "Login page must have .auth-divider");
    assertCondition(str_contains($loginContent, 'hoặc'), "Login page must have 'hoặc' divider text");

    // Google button check
    assertCondition(str_contains($loginContent, 'id="btn-google-login"'), "Login page must have #btn-google-login");
    assertCondition(str_contains($loginContent, 'class="btn btn-google"'), "Login button must use .btn-google class");
    assertCondition(str_contains($loginContent, 'href="/auth/google/start"'), "Login button must target /auth/google/start without role");
    assertCondition(str_contains($loginContent, 'aria-label="Tiếp tục với Google"'), "Login button must have accessible aria-label");
    assertCondition(str_contains($loginContent, 'Tiếp tục với Google'), "Login button must display 'Tiếp tục với Google'");
    assertCondition(str_contains($loginContent, '<svg class="btn-google-icon"'), "Login button must contain Google SVG icon");

    // Preserved password form check
    assertCondition(str_contains($loginContent, 'id="login-form"'), "Login page must preserve #login-form");
    assertCondition(str_contains($loginContent, 'id="login-email"'), "Login page must preserve #login-email");
    assertCondition(str_contains($loginContent, 'id="login-password"'), "Login page must preserve #login-password");
    assertCondition(str_contains($loginContent, 'id="btn-submit-login"'), "Login page must preserve #btn-submit-login");

    // Safe error display check (anti-XSS)
    assertCondition(str_contains($loginContent, 'errorBox.textContent = oauthError'), "Login page must use textContent for query error to prevent XSS");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 2: Register Page Markup & Role Selector Placement
    // =========================================================================
    echo "Running Test 2: Register Page Markup & Role Selector Placement... ";
    $registerContent = file_get_contents(__DIR__ . "/app/Views/auth/register.php");
    assertCondition($registerContent !== false, "register.php must exist");

    // Position check: Google button must appear AFTER role selector and BEFORE password form
    $roleTabsPos = strpos($registerContent, 'id="tab-student"');
    $googleBtnPos = strpos($registerContent, 'id="btn-google-register"');
    $dividerPos = strpos($registerContent, 'class="auth-divider"');
    $formPos = strpos($registerContent, 'id="register-form"');

    assertCondition($roleTabsPos !== false, "Register page must have role tabs");
    assertCondition($googleBtnPos !== false, "Register page must have #btn-google-register");
    assertCondition($dividerPos !== false, "Register page must have .auth-divider");
    assertCondition($formPos !== false, "Register page must have #register-form");

    assertCondition($roleTabsPos < $googleBtnPos, "Google button must be placed below role selector tabs");
    assertCondition($googleBtnPos < $dividerPos, "Divider must be placed after Google button");
    assertCondition($dividerPos < $formPos, "Email/password form must be placed after divider");

    // Google button attributes
    assertCondition(str_contains($registerContent, 'class="btn btn-google"'), "Register Google button must use .btn-google");
    assertCondition(str_contains($registerContent, 'href="/auth/google/start?role=student"'), "Register Google button must initially bind role=student");
    assertCondition(str_contains($registerContent, 'aria-label="Đăng ký bằng Google với vai trò sinh viên"'), "Register Google button must have student aria-label");
    assertCondition(str_contains($registerContent, '<svg class="btn-google-icon"'), "Register button must contain Google SVG icon");

    // Preserved password form check
    assertCondition(str_contains($registerContent, 'id="register-name"'), "Register page must preserve #register-name");
    assertCondition(str_contains($registerContent, 'id="register-email"'), "Register page must preserve #register-email");
    assertCondition(str_contains($registerContent, 'id="register-password"'), "Register page must preserve #register-password");
    assertCondition(str_contains($registerContent, 'id="register-password-confirm"'), "Register page must preserve #register-password-confirm");
    assertCondition(str_contains($registerContent, 'id="btn-submit-register"'), "Register page must preserve #btn-submit-register");

    // Safe error display check (anti-XSS)
    assertCondition(str_contains($registerContent, 'errorBox.textContent = oauthError'), "Register page must use textContent for query error to prevent XSS");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 3: Strict Absence of Admin Option
    // =========================================================================
    echo "Running Test 3: Strict Absence of Admin Option on Register Page... ";
    assertCondition(!str_contains($registerContent, 'role="admin"'), "Register page must never offer role admin");
    assertCondition(!str_contains($registerContent, "role='admin'"), "Register page must never offer role admin");
    assertCondition(!str_contains($registerContent, 'value="admin"'), "Register page must never offer value admin");
    assertCondition(str_contains($registerContent, 'if (role !== "student" && role !== "company")'), "setRegisterRole must guard against non-student/company roles");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 4: CSS Styles Verification
    // =========================================================================
    echo "Running Test 4: CSS Styles Verification in style.css... ";
    $cssContent = file_get_contents(__DIR__ . "/public/assets/css/style.css");
    assertCondition($cssContent !== false, "style.css must exist");

    assertCondition(str_contains($cssContent, '.auth-divider'), "style.css must contain .auth-divider");
    assertCondition(str_contains($cssContent, '.btn-google'), "style.css must contain .btn-google");
    assertCondition(str_contains($cssContent, '.btn-google:hover'), "style.css must contain .btn-google:hover");
    assertCondition(str_contains($cssContent, '.btn-google:focus-visible'), "style.css must contain .btn-google:focus-visible");
    assertCondition(str_contains($cssContent, '.btn-google:disabled'), "style.css must contain .btn-google:disabled");
    assertCondition(str_contains($cssContent, '.btn-google-icon'), "style.css must contain .btn-google-icon");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 5: Backend Start without Role Intent (Login Flow)
    // =========================================================================
    echo "Running Test 5: Backend Start without Role Intent (Login Flow)... ";
    $mockClient = new MockP1Client();
    $stateStore = new OAuthStateStore($tempStateDir);
    $oauthRepo = new OAuthIdentityRepository($db);
    $authRepo = new AuthenticationRepository($db);
    $service = new GoogleOAuthService($mockClient, $stateStore, $oauthRepo, $authRepo, $db);

    // Call start with null role (as from /login button)
    $authUrl = $service->start(null);
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"] ?? "";
    $nonce = $params["nonce"] ?? "";

    assertCondition(!empty($state), "State must be generated when role is null");
    assertCondition(!empty($nonce), "Nonce must be generated when role is null");

    // Inspect persisted state: role should be null
    $stateData = $stateStore->consume($state);
    assertCondition($stateData !== null, "State must be persisted in store");
    assertCondition($stateData["role"] === null, "Stored role must be null when starting without role intent");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 6: Backend Callback for Linked Student (Logged in via No-Role Start)
    // =========================================================================
    echo "Running Test 6: Backend Callback for Linked Student (No-Role Start)... ";
    $s6Id = "{$fixturePrefix}user_student6";
    $s6Email = "{$fixturePrefix}student6@jobmarket.vn";
    $s6Sub = "{$fixturePrefix}sub_s6";

    // Seed existing user + identity
    $stmtUser = $db->prepare("INSERT INTO users (id, name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    $stmtUser->execute([$s6Id, "Linked Student Six", $s6Email, password_hash("Secret123!", PASSWORD_BCRYPT), "student"]);

    $stmtId = $db->prepare("INSERT INTO oauth_identities (id, user_id, provider, provider_subject, email_at_link, created_at) VALUES (?, ?, 'google', ?, ?, NOW())");
    $stmtId->execute(["{$fixturePrefix}id_s6", $s6Id, $s6Sub, $s6Email]);

    // Start with null role
    $authUrl = $service->start(null);
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
        "name"           => "Linked Student Six"
    ];

    $result = $service->callback("mock_code_s6", $state);
    assertCondition(!empty($result["token"]), "JWT token must be issued");
    assertCondition($result["user"]["role"] === "student", "User role must be student from DB");
    assertCondition($result["user"]["id"] === $s6Id, "User ID must match existing user");
    assertCondition($result["is_new_user"] === false, "is_new_user must be false");
    assertCondition($result["redirect"] === "/student/dashboard", "Redirect destination must be /student/dashboard");

    $decoded = JWT::decode($result["token"]);
    assertCondition($decoded["role"] === "student", "Decoded JWT role must be student");
    assertCondition($decoded["id"] === $s6Id, "Decoded JWT id must match existing user");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 7: Backend Callback for Linked Company (Logged in via No-Role Start)
    // =========================================================================
    echo "Running Test 7: Backend Callback for Linked Company (No-Role Start)... ";
    $c7Id = "{$fixturePrefix}user_company7";
    $c7Email = "{$fixturePrefix}company7@jobmarket.vn";
    $c7Sub = "{$fixturePrefix}sub_c7";

    // Seed existing company user + identity + company
    $stmtUser->execute([$c7Id, "Linked Company Seven", $c7Email, password_hash("Secret123!", PASSWORD_BCRYPT), "company"]);
    $stmtComp = $db->prepare("INSERT INTO companies (id, user_id, name, verification_status, created_at) VALUES (?, ?, ?, 'verified', NOW())");
    $stmtComp->execute(["{$fixturePrefix}comp_c7", $c7Id, "Company Seven Corp"]);
    $stmtId->execute(["{$fixturePrefix}id_c7", $c7Id, $c7Sub, $c7Email]);

    // Start with null role
    $authUrl = $service->start(null);
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $c7Sub,
        "email"          => $c7Email,
        "email_verified" => true,
        "name"           => "Linked Company Seven"
    ];

    $result = $service->callback("mock_code_c7", $state);
    assertCondition(!empty($result["token"]), "JWT token must be issued");
    assertCondition($result["user"]["role"] === "company", "User role must be company from DB");
    assertCondition($result["user"]["id"] === $c7Id, "User ID must match existing company");
    assertCondition($result["is_new_user"] === false, "is_new_user must be false");
    assertCondition($result["redirect"] === "/company/dashboard", "Redirect destination must be /company/dashboard");

    $decoded = JWT::decode($result["token"]);
    assertCondition($decoded["role"] === "company", "Decoded JWT role must be company");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 8: Backend Callback for First-Time User with No-Role Start
    // =========================================================================
    echo "Running Test 8: First-Time User with No-Role Start Rejected (Must Register)... ";
    $s8Email = "{$fixturePrefix}new_user8@jobmarket.vn";
    $s8Sub = "{$fixturePrefix}sub_new8";

    // User starts from /login (no role in state)
    $authUrl = $service->start(null);
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state = $params["state"];
    $nonce = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce,
        "sub"            => $s8Sub,
        "email"          => $s8Email,
        "email_verified" => true,
        "name"           => "New Google User"
    ];

    $caught = false;
    $errorMessage = "";
    try {
        $service->callback("mock_code_8", $state);
    } catch (ValidationException $e) {
        $caught = true;
        $errorMessage = $e->getMessage();
    }
    assertCondition($caught, "First-time user logging in without role must throw ValidationException");
    assertCondition(str_contains($errorMessage, "đăng ký"), "Error message must instruct user to register: {$errorMessage}");

    // Verify no user was created
    $checkUser = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $checkUser->execute([$s8Email]);
    assertCondition((int)$checkUser->fetchColumn() === 0, "No user must be created when starting without role");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 9: Backend Start with Invalid Role (admin, employer, etc.)
    // =========================================================================
    echo "Running Test 9: Backend Start Rejects Invalid Roles... ";
    $rejectedRoles = ["admin", "root", "administrator", "employer", "developer", " "];
    foreach ($rejectedRoles as $badRole) {
        $caught = false;
        try {
            $service->start($badRole);
        } catch (ValidationException) {
            $caught = true;
        }
        assertCondition($caught, "Starting with role '{$badRole}' must throw ValidationException");
    }
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 10: Controller Start Integration (HTTP 302 Redirects)
    // =========================================================================
    echo "Running Test 10: Controller Start Integration... ";
    $controller = new GoogleOAuthController($service);

    // 10a. Start with no role (HTML request from /login)
    $reqNoRole = new Request([], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/start",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resNoRole = $controller->start($reqNoRole);
    assertCondition($resNoRole->getStatusCode() === 302, "Start without role must redirect (302)");
    assertCondition(str_starts_with($resNoRole->getHeader("Location"), "https://accounts.google.com"), "Redirect must point to Google");

    // 10b. Start with role=student
    $reqStudent = new Request(["role" => "student"], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/start?role=student",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resStudent = $controller->start($reqStudent);
    assertCondition($resStudent->getStatusCode() === 302, "Start with student role must redirect (302)");

    // 10c. Start with role=company
    $reqCompany = new Request(["role" => "company"], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/start?role=company",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resCompany = $controller->start($reqCompany);
    assertCondition($resCompany->getStatusCode() === 302, "Start with company role must redirect (302)");

    // 10d. Start with role=admin (HTML request -> redirects to /login?error=... without oauth_error marker)
    $reqAdmin = new Request(["role" => "admin"], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/start?role=admin",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resAdmin = $controller->start($reqAdmin);
    assertCondition($resAdmin->getStatusCode() === 302, "Start with admin role must redirect to login with error");
    $locAdmin = $resAdmin->getHeader("Location");
    assertCondition(str_contains($locAdmin, "/login?error="), "Admin attempt must redirect to /login with error param");
    assertCondition(!str_contains($locAdmin, "oauth_error"), "Start error must NOT include oauth_error marker (prevents logout CSRF)");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 11: Callback Google Error WITHOUT State Has NO oauth_error Marker
    // =========================================================================
    echo "Running Test 11: Callback Google Error WITHOUT State Has NO oauth_error Marker... ";
    $reqGoogleErrNoState = new Request(["error" => "access_denied"], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/callback?error=access_denied",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resGoogleErrNoState = $controller->callback($reqGoogleErrNoState);
    assertCondition($resGoogleErrNoState->getStatusCode() === 302, "Google error response must redirect 302");
    $locGoogleErrNoState = $resGoogleErrNoState->getHeader("Location");
    assertCondition(str_contains($locGoogleErrNoState, "/login?error="), "Location must point to /login?error=");
    assertCondition(!str_contains($locGoogleErrNoState, "oauth_error"), "Unauthenticated Google error without state must NOT include oauth_error marker");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 12: Callback Google Error with Invalid or Expired State Has NO Marker
    // =========================================================================
    echo "Running Test 12: Callback Google Error with Invalid State Has NO Marker... ";
    $reqGoogleErrBadState = new Request(["error" => "access_denied", "state" => "non_existent_state_xyz"], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/callback?error=access_denied&state=non_existent_state_xyz",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resGoogleErrBadState = $controller->callback($reqGoogleErrBadState);
    assertCondition($resGoogleErrBadState->getStatusCode() === 302, "Google error with bad state must redirect 302");
    $locGoogleErrBadState = $resGoogleErrBadState->getHeader("Location");
    assertCondition(str_contains($locGoogleErrBadState, "/login?error="), "Location must point to /login?error=");
    assertCondition(!str_contains($locGoogleErrBadState, "oauth_error"), "Google error with invalid state must NOT include oauth_error marker");

    // Also check standard callback code exchange with bad state
    $reqBadCodeState = new Request(["code" => "some_code", "state" => "invalid_state_xyz"], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/callback?code=some_code&state=invalid_state_xyz",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resBadCodeState = $controller->callback($reqBadCodeState);
    assertCondition($resBadCodeState->getStatusCode() === 302, "Bad state must redirect 302");
    $locBadCodeState = $resBadCodeState->getHeader("Location");
    assertCondition(str_contains($locBadCodeState, "/login?error="), "Location must point to /login?error=");
    assertCondition(!str_contains($locBadCodeState, "oauth_error"), "Bad state in code exchange must NOT include oauth_error marker");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 13: Callback Error with Valid Pending State Has oauth_error=1 & Consumes State
    // =========================================================================
    echo "Running Test 13: Callback Error with Valid Pending State Has oauth_error=1 & Consumes State... ";
    $validPendingState = "valid_pending_state_{$runId}_13";
    $stateStore->save($validPendingState, ["nonce" => "nonce_13", "role" => "student"]);

    $reqValidPending = new Request(["error" => "access_denied", "state" => $validPendingState], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/callback?error=access_denied&state={$validPendingState}",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resValidPending = $controller->callback($reqValidPending);
    assertCondition($resValidPending->getStatusCode() === 302, "Valid pending state must redirect 302");
    $locValidPending = $resValidPending->getHeader("Location");
    assertCondition(str_contains($locValidPending, "/login?error="), "Location must point to /login?error=");
    assertCondition(str_contains($locValidPending, "oauth_error=1"), "Valid pending state failure MUST include oauth_error=1");

    // State must be atomically consumed
    assertCondition($stateStore->consume($validPendingState) === null, "Pending state must be consumed by the callback error handler");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 14: Replayed State CANNOT Produce oauth_error Marker
    // =========================================================================
    echo "Running Test 14: Replayed State CANNOT Produce oauth_error Marker... ";
    // Replay the exact same request with the now-consumed state
    $resReplay = $controller->callback($reqValidPending);
    assertCondition($resReplay->getStatusCode() === 302, "Replayed state must redirect 302");
    $locReplay = $resReplay->getHeader("Location");
    assertCondition(str_contains($locReplay, "/login?error="), "Location must point to /login?error=");
    assertCondition(!str_contains($locReplay, "oauth_error"), "Replayed state MUST NOT produce oauth_error marker");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 15: Valid State Failure (Post-Consumption Exception) Has oauth_error=1
    // =========================================================================
    echo "Running Test 15: Valid State Failure (Post-Consumption Exception) Has oauth_error=1... ";
    // Start with null role
    $authUrl = $service->start(null);
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $stateNullRole = $params["state"];
    $nonceNullRole = $params["nonce"];

    $unlinkedEmail = "{$fixturePrefix}unlinked_user15@jobmarket.vn";
    $unlinkedSub = "{$fixturePrefix}sub_unlinked15";
    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonceNullRole,
        "sub"            => $unlinkedSub,
        "email"          => $unlinkedEmail,
        "email_verified" => true,
        "name"           => "Unlinked User Fifteen"
    ];

    $reqValidationErr = new Request(["code" => "code_unlinked15", "state" => $stateNullRole], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/callback?code=code_unlinked15&state={$stateNullRole}",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resValidationErr = $controller->callback($reqValidationErr);
    assertCondition($resValidationErr->getStatusCode() === 302, "Validation error must redirect 302");
    $locValidationErr = $resValidationErr->getHeader("Location");
    assertCondition(str_contains($locValidationErr, "/login?error="), "Location must point to /login?error=");
    assertCondition(str_contains($locValidationErr, "oauth_error=1"), "Post-consumption failure MUST include oauth_error=1 marker");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 16: Successful Callback Does NOT Include oauth_error Marker
    // =========================================================================
    echo "Running Test 16: Successful Callback Does NOT Include oauth_error Marker... ";
    $s16Email = "{$fixturePrefix}student16@jobmarket.vn";
    $s16Sub = "{$fixturePrefix}sub_16";

    $authUrl = $service->start("student");
    parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
    $state16 = $params["state"];
    $nonce16 = $params["nonce"];

    $mockClient->claimsToReturn = [
        "iss"            => "https://accounts.google.com",
        "aud"            => $mockClient->clientId,
        "exp"            => time() + 3600,
        "nonce"          => $nonce16,
        "sub"            => $s16Sub,
        "email"          => $s16Email,
        "email_verified" => true,
        "name"           => "Student Sixteen"
    ];

    $reqSuccess = new Request(["code" => "code_16", "state" => $state16], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI" => "/auth/google/callback?code=code_16&state={$state16}",
        "HTTP_ACCEPT" => "text/html,application/xhtml+xml"
    ]);
    $resSuccess = $controller->callback($reqSuccess);
    assertCondition($resSuccess->getStatusCode() === 200, "Successful HTML callback must return HTTP 200");
    $successHtml = $resSuccess->getPayload();

    // Verify successful client handoff code
    assertCondition(str_contains($successHtml, "jobmarket_token"), "Success HTML must store jobmarket_token");
    assertCondition(str_contains($successHtml, "jobmarket_user"), "Success HTML must store jobmarket_user");
    assertCondition(str_contains($successHtml, "window.location.replace("), "Success HTML must navigate to redirect destination");
    // Main redirect must NOT contain oauth_error
    assertCondition(!str_contains($successHtml, "/student/profile?oauth_error="), "Success redirect target must not contain oauth_error");
    // Fallback catch script DOES contain oauth_error=1
    assertCondition(str_contains($successHtml, "&oauth_error=1"), "Fallback script catch must contain &oauth_error=1");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 17: Guarded TokenStorage.clear() Handling in Views
    // =========================================================================
    echo "Running Test 17: Guarded TokenStorage.clear() Handling in Views... ";
    // Check login.php
    assertCondition(str_contains($loginContent, 'urlParams.get("oauth_error") === "1"'), "login.php must explicitly check oauth_error === '1'");
    assertCondition(str_contains($loginContent, 'TokenStorage.clear()'), "login.php must call TokenStorage.clear()");
    assertCondition(str_contains($loginContent, 'if (isOAuthError)'), "login.php must guard session clearing with isOAuthError");
    assertCondition(str_contains($loginContent, 'window.history.replaceState'), "login.php must support removing OAuth error params via replaceState");

    // Check register.php
    assertCondition(str_contains($registerContent, 'urlParams.get("oauth_error") === "1"'), "register.php must explicitly check oauth_error === '1'");
    assertCondition(str_contains($registerContent, 'TokenStorage.clear()'), "register.php must call TokenStorage.clear()");
    assertCondition(str_contains($registerContent, 'if (isOAuthError)'), "register.php must guard session clearing with isOAuthError");
    assertCondition(str_contains($registerContent, 'window.history.replaceState'), "register.php must support removing OAuth error params via replaceState");
    echo "PASSED\n";
    $passedTests++;

    // =========================================================================
    // TEST 18: Token, Auth Code & Secret Privacy in Error Redirects & Views
    // =========================================================================
    echo "Running Test 18: Token, Auth Code & Secret Privacy... ";
    $allRedirects = [
        $locGoogleErrNoState,
        $locGoogleErrBadState,
        $locBadCodeState,
        $locValidPending,
        $locReplay,
        $locValidationErr,
        $locAdmin
    ];
    $forbiddenSecrets = [
        "client_secret",
        "mock-google-access-token",
        "mock.id.token",
        "code_unlinked15",
        "Secret123!"
    ];

    foreach ($allRedirects as $idx => $redir) {
        foreach ($forbiddenSecrets as $secret) {
            assertCondition(!str_contains($redir, $secret), "Redirect #{$idx} ('{$redir}') must NEVER contain '{$secret}'");
        }
    }

    foreach ($forbiddenSecrets as $secret) {
        assertCondition(!str_contains($loginContent, $secret), "login.php must NEVER contain secret '{$secret}'");
        assertCondition(!str_contains($registerContent, $secret), "register.php must NEVER contain secret '{$secret}'");
        assertCondition(!str_contains($successHtml, "mock-google-access-token"), "Callback HTML must NEVER expose Google access token");
        assertCondition(!str_contains($successHtml, "mock.id.token"), "Callback HTML must NEVER expose Google raw ID token");
    }
    echo "PASSED\n";
    $passedTests++;

    echo "\n=================================================================\n";
    echo "   KẾT QUẢ KIỂM THỬ: {$passedTests}/{$passedTests} PASSED (100%)\n";
    echo "   TẤT CẢ TIÊU CHÍ GOOGLE-AUTH-P1-01 ĐẠT CHUẨN!\n";
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
