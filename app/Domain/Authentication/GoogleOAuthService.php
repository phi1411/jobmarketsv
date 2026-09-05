<?php

namespace JobMarket\Domain\Authentication;

use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Infrastructure\AuthenticationRepository;
use JobMarket\Infrastructure\GoogleOAuthClient;
use JobMarket\Infrastructure\OAuthIdentityRepository;
use JobMarket\Infrastructure\OAuthStateStore;
use PDO;

class GoogleOAuthService
{
    private GoogleOAuthClientInterface $client;
    private OAuthStateStoreInterface $stateStore;
    private OAuthIdentityRepositoryInterface $oauthRepo;
    private AuthenticationRepository $authRepo;
    private PDO $db;

    public function __construct(
        ?GoogleOAuthClientInterface $client = null,
        ?OAuthStateStoreInterface $stateStore = null,
        ?OAuthIdentityRepositoryInterface $oauthRepo = null,
        ?AuthenticationRepository $authRepo = null,
        ?PDO $db = null
    ) {
        $this->client = $client ?? new GoogleOAuthClient();
        $this->stateStore = $stateStore ?? new OAuthStateStore();
        $this->oauthRepo = $oauthRepo ?? new OAuthIdentityRepository();
        $this->authRepo = $authRepo ?? new AuthenticationRepository();

        if ($db !== null) {
            $this->db = $db;
        } elseif ($this->oauthRepo instanceof OAuthIdentityRepository) {
            $this->db = $this->oauthRepo->getDB();
        } else {
            $config = Config::env();
            $this->db = new PDO(
                "mysql:dbname={$config['dbname']};host={$config['host']}",
                $config["user"],
                $config["password"],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
    }

    public function start(?string $role = null, ?string $returnUrl = null): string
    {
        $normalizedRole = null;
        if ($role !== null) {
            $normalizedRole = strtolower(trim($role));
            // Start accepts only Student or Company intent if role is provided
            if (!in_array($normalizedRole, ["student", "company"], true)) {
                throw new ValidationException(
                    ["role" => ["Vai trò không hợp lệ. Google Sign-In chỉ hỗ trợ vai trò 'student' hoặc 'company'."]],
                    "Vai trò không hợp lệ"
                );
            }
        }

        // Canonicalize and validate internal return path
        $validatedReturnUrl = $this->validateInternalReturnUrl($returnUrl, $normalizedRole);

        // Generate CSPRNG state and nonce
        $state = bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));

        // Persist role, internal return path, state, and nonce server-side with short TTL (300s = 5m)
        $this->stateStore->save($state, [
            "nonce"      => $nonce,
            "role"       => $normalizedRole,
            "return_url" => $validatedReturnUrl,
            "created_at" => time()
        ], 300);

        return $this->client->getAuthorizationUrl($state, $nonce);
    }

    public function consumeState(string $state): ?array
    {
        $trimmedState = trim($state);
        if (empty($trimmedState) || strlen($trimmedState) > 256) {
            return null;
        }

        return $this->stateStore->consume($trimmedState);
    }

    public function callback(string $code, string $state): array
    {
        $trimmedCode = trim($code);
        $trimmedState = trim($state);

        if (empty($trimmedCode) || empty($trimmedState)) {
            throw new AuthenticationException("Mã ủy quyền hoặc trạng thái xác thực không được để trống.");
        }

        // One-time atomic consumption of state
        $stateData = $this->consumeState($trimmedState);
        if (!$stateData) {
            throw new AuthenticationException("Trạng thái phiên xác thực (state) không hợp lệ hoặc đã hết hạn.");
        }

        return $this->processCallback($trimmedCode, $stateData);
    }

    public function processCallback(string $code, array $stateData): array
    {
        $trimmedCode = trim($code);
        if (empty($trimmedCode)) {
            throw new AuthenticationException("Mã ủy quyền hoặc trạng thái xác thực không được để trống.");
        }

        $expectedNonce = $stateData["nonce"] ?? "";
        $stateRole = $stateData["role"] ?? null;
        $storedReturnUrl = $stateData["return_url"] ?? null;

        if ($stateRole !== null && !in_array($stateRole, ["student", "company"], true)) {
            throw new AuthenticationException("Vai trò lưu trong phiên xác thực không hợp lệ.");
        }

        // Exchange authorization code server-side
        $tokenData = $this->client->exchangeCode($trimmedCode);
        $idToken = $tokenData["id_token"] ?? "";
        if (empty($idToken)) {
            throw new AuthenticationException("Không nhận được ID token từ Google.");
        }

        // Validate ID token signature, issuer, audience, expiry, nonce, subject, and email_verified
        $claims = $this->client->verifyIdToken($idToken, $expectedNonce);

        $sub = trim((string)($claims["sub"] ?? ""));
        $email = strtolower(trim((string)($claims["email"] ?? "")));
        $name = trim((string)($claims["name"] ?? ""));
        if (empty($name)) {
            $name = explode("@", $email)[0];
        }

        if (empty($sub)) {
            throw new AuthenticationException("Không tìm thấy thông tin sub trong Google ID token.");
        }

        // Check if identity already exists
        $identity = $this->oauthRepo->findIdentity("google", $sub);

        if ($identity !== null) {
            // Existing linked Google user
            $user = $this->authRepo->findUserRecordByIdOrEmail($identity->getUserId(), null);
            if (!$user) {
                throw new AuthenticationException("Tài khoản người dùng liên kết không tồn tại trên hệ thống.");
            }

            // Enforce account status before JWT issuance
            $status = $user["status"] ?? "active";
            if ($status === "banned") {
                throw new AuthenticationException("Tài khoản của bạn đã bị khóa.");
            }
            if ($status === "suspended") {
                throw new AuthenticationException("Tài khoản của bạn đang bị tạm khóa.");
            }
            if ($status !== "active") {
                throw new AuthenticationException("Tài khoản chưa được kích hoạt hoặc không hợp lệ.");
            }

            $jwtToken = JWT::encode([
                "id"    => $user["id"],
                "email" => $user["email"],
                "role"  => $user["role"]
            ]);

            $this->persistSessionToken($user["id"], $jwtToken);

            $userPayload = [
                "id"    => $user["id"],
                "name"  => $user["name"],
                "email" => $user["email"],
                "role"  => $user["role"]
            ];
            $isNewUser = false;
            $userRole = $user["role"];
        } else {
            // First-time user
            // If user initiated OAuth from Login without selecting a role, reject and guide to Register
            if (empty($stateRole)) {
                throw new ValidationException(
                    ["role" => ["Tài khoản Google chưa được liên kết với hệ thống. Vui lòng đăng ký tài khoản mới và chọn vai trò trước."]],
                    "Tài khoản Google chưa được liên kết với hệ thống. Vui lòng đăng ký tài khoản mới và chọn vai trò trước."
                );
            }

            // Preserve no-auto-link policy: do not link to existing local account with same email
            $localUser = $this->oauthRepo->findLocalUserByEmail($email);
            if ($localUser !== null) {
                throw new ValidationException(
                    ["email" => ["Email này đã tồn tại trên hệ thống. Vui lòng đăng nhập bằng mật khẩu."]],
                    "Email này đã tồn tại trên hệ thống. Vui lòng đăng nhập bằng mật khẩu."
                );
            }

            // Create first-time Student or Company through P0-01 repository
            $createResult = $this->oauthRepo->createOAuthUserWithIdentity(
                [
                    "name"  => $name,
                    "email" => $email,
                    "role"  => $stateRole
                ],
                [
                    "provider"         => "google",
                    "provider_subject" => $sub,
                    "email_at_link"    => $email
                ]
            );

            $newUser = $createResult["user"];

            $jwtToken = JWT::encode([
                "id"    => $newUser["id"],
                "email" => $newUser["email"],
                "role"  => $newUser["role"]
            ]);

            $this->persistSessionToken($newUser["id"], $jwtToken);

            $userPayload = [
                "id"    => $newUser["id"],
                "name"  => $newUser["name"],
                "email" => $newUser["email"],
                "role"  => $newUser["role"]
            ];
            $isNewUser = true;
            $userRole = $newUser["role"];
        }

        $redirectUrl = $this->resolveSafeRedirectUrl($storedReturnUrl, $userRole, $isNewUser);

        return [
            "token"       => $jwtToken,
            "user"        => $userPayload,
            "redirect"    => $redirectUrl,
            "is_new_user" => $isNewUser
        ];
    }

    /**
     * Strictly canonicalize and validate internal return paths:
     * - Rejects control characters, null bytes, raw backslashes.
     * - Rejects URL schemes (contains ':') and encoded colons (%3a).
     * - Rejects encoded control chars, encoded slashes (%2f), encoded backslashes (%5c).
     * - Rejects protocol-relative URLs (starts with '//').
     * - Rejects raw or encoded dot segments ('.' or '..' or '%2e').
     * - Canonicalizes path segments and enforces allowlist by role.
     */
    public function validateInternalReturnUrl(?string $url, ?string $role = null): ?string
    {
        if ($url === null || $url === "") {
            return null;
        }

        // 1. Reject control characters or null bytes in raw input
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        // 2. Reject untrimmed whitespace or trailing control characters
        if ($url !== trim($url)) {
            return null;
        }

        $trimmed = $url;

        // 2. Reject encoded control characters, null bytes, slashes, or backslashes
        if (preg_match('/%(?:0[0-9a-fA-F]|2[fF]|5[cC])/i', $trimmed)) {
            return null;
        }

        // 3. Reject raw backslashes
        if (str_contains($trimmed, "\\")) {
            return null;
        }

        // 4. Reject schemes (contains ':') or encoded colons (%3a)
        if (str_contains($trimmed, ":") || stripos($trimmed, "%3a") !== false) {
            return null;
        }

        // 5. Must start with single '/', and not protocol-relative '//'
        if (!str_starts_with($trimmed, "/") || str_starts_with($trimmed, "//")) {
            return null;
        }

        // 6. Reject raw or encoded dot segments (path traversal)
        if (preg_match('/%(?:2[eE])/i', $trimmed) || str_contains($trimmed, '/.') || str_contains($trimmed, './') || $trimmed === '.' || $trimmed === '..') {
            return null;
        }

        // Parse path component (before query or fragment)
        $parsedPath = parse_url($trimmed, PHP_URL_PATH);
        if ($parsedPath === null || $parsedPath === false || !str_starts_with($parsedPath, "/")) {
            return null;
        }

        // Canonicalize path segments (resolve '.' and '..')
        $rawSegments = explode('/', $parsedPath);
        $canonicalSegments = [];
        foreach ($rawSegments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                // Path traversal attempt: reject immediately
                return null;
            }
            $canonicalSegments[] = $segment;
        }

        $canonicalPath = '/' . implode('/', $canonicalSegments);
        if (str_ends_with($parsedPath, '/') && $canonicalPath !== '/') {
            $canonicalPath .= '/';
        }

        // Never allow admin return URL for public Google OAuth
        if ($canonicalPath === "/admin" || str_starts_with($canonicalPath, "/admin/")) {
            return null;
        }

        // 7. Check allowlisted prefixes by role
        if ($role === null) {
            $allowed = false;
            if ($canonicalPath === "/") {
                $allowed = true;
            } else {
                $allowedPrefixes = ["/student/", "/company/", "/jobs", "/viec-lam", "/profile", "/developers", "/companies"];
                foreach ($allowedPrefixes as $prefix) {
                    if ($canonicalPath === rtrim($prefix, "/") || str_starts_with($canonicalPath, $prefix)) {
                        $allowed = true;
                        break;
                    }
                }
            }
            if (!$allowed) {
                return null;
            }
        } elseif ($role === "student") {
            $allowed = false;
            if ($canonicalPath === "/") {
                $allowed = true;
            } else {
                $allowedPrefixes = ["/student/", "/jobs", "/viec-lam", "/profile", "/developers"];
                foreach ($allowedPrefixes as $prefix) {
                    if ($canonicalPath === rtrim($prefix, "/") || str_starts_with($canonicalPath, $prefix)) {
                        $allowed = true;
                        break;
                    }
                }
            }
            if (!$allowed) {
                return null;
            }
        } elseif ($role === "company") {
            $allowed = false;
            if ($canonicalPath === "/") {
                $allowed = true;
            } else {
                $allowedPrefixes = ["/company/", "/jobs", "/viec-lam", "/companies"];
                foreach ($allowedPrefixes as $prefix) {
                    if ($canonicalPath === rtrim($prefix, "/") || str_starts_with($canonicalPath, $prefix)) {
                        $allowed = true;
                        break;
                    }
                }
            }
            if (!$allowed) {
                return null;
            }
        } else {
            return null;
        }

        // Reconstruct safe internal path with query/fragment if present
        $query = parse_url($trimmed, PHP_URL_QUERY);
        $fragment = parse_url($trimmed, PHP_URL_FRAGMENT);

        $result = $canonicalPath;
        if ($query !== null && $query !== "") {
            $result .= "?" . $query;
        }
        if ($fragment !== null && $fragment !== "") {
            $result .= "#" . $fragment;
        }

        return $result;
    }

    public function resolveSafeRedirectUrl(?string $storedReturnUrl, string $role, bool $isNewUser): string
    {
        if (!empty($storedReturnUrl)) {
            $validated = $this->validateInternalReturnUrl($storedReturnUrl, $role);
            if ($validated !== null) {
                return $validated;
            }
        }

        if ($role === "student") {
            return $isNewUser ? "/student/profile" : "/student/dashboard";
        }

        if ($role === "company") {
            return $isNewUser ? "/company/profile" : "/company/dashboard";
        }

        return "/";
    }

    private function persistSessionToken(string $userId, string $token): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET token = ?, token_expires_at = ? WHERE id = ?"
        );
        $stmt->execute([
            $token,
            date('Y-m-d H:i:s', time() + 2592000),
            $userId
        ]);
    }
}
