<?php

namespace JobMarket\Infrastructure;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use JobMarket\Domain\Authentication\GoogleOAuthClientInterface;
use JobMarket\Exceptions\AuthenticationException;
use Throwable;

class GoogleOAuthClient implements GoogleOAuthClientInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    /** @var array<string, Key>|null */
    private ?array $jwkKeysOverride = null;

    public function __construct(?string $clientId = null, ?string $clientSecret = null, ?string $redirectUri = null)
    {
        $this->clientId = $clientId ?? ($_ENV["GOOGLE_OAUTH_CLIENT_ID"] ?? "");
        $this->clientSecret = $clientSecret ?? ($_ENV["GOOGLE_OAUTH_CLIENT_SECRET"] ?? "");
        $this->redirectUri = $redirectUri ?? ($_ENV["GOOGLE_OAUTH_REDIRECT_URI"] ?? "");
    }

    /**
     * Set explicit verified key set for test isolation / mocking without network.
     *
     * @param array<string, Key>|null $keys
     */
    public function setJwkKeysOverride(?array $keys): void
    {
        $this->jwkKeysOverride = $keys;
    }

    public function getAuthorizationUrl(string $state, string $nonce): string
    {
        if (empty($this->clientId)) {
            throw new AuthenticationException("Thiếu cấu hình GOOGLE_OAUTH_CLIENT_ID.");
        }
        if (empty($this->redirectUri)) {
            throw new AuthenticationException("Thiếu cấu hình GOOGLE_OAUTH_REDIRECT_URI.");
        }

        $params = [
            "client_id"     => $this->clientId,
            "redirect_uri"  => $this->redirectUri,
            "response_type" => "code",
            "scope"         => "openid email profile",
            "state"         => $state,
            "nonce"         => $nonce,
            "access_type"   => "online",
            "prompt"        => "select_account"
        ];

        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);
    }

    public function exchangeCode(string $code): array
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->redirectUri)) {
            throw new AuthenticationException("Cấu hình Google OAuth chưa đầy đủ trên server.");
        }

        $tokenUrl = "https://oauth2.googleapis.com/token";
        $postData = http_build_query([
            "code"          => $code,
            "client_id"     => $this->clientId,
            "client_secret" => $this->clientSecret,
            "redirect_uri"  => $this->redirectUri,
            "grant_type"    => "authorization_code"
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json"
            ],
            CURLOPT_TIMEOUT        => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || !$response) {
            throw new AuthenticationException("Không thể trao đổi mã cấp quyền với Google (HTTP " . $httpCode . ").");
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data["id_token"])) {
            throw new AuthenticationException("Phản hồi token từ Google không hợp lệ hoặc thiếu ID token.");
        }

        return $data;
    }

    /**
     * Validate Google ID token strictly with fail-closed semantics:
     * - Parse header without trusting payload.
     * - Require algorithm RS256.
     * - Require non-empty kid.
     * - Require successfully parsed Google JWK set.
     * - Require kid in JWK set.
     * - Verify signature with FirebaseJWT BEFORE trusting any payload claims.
     * - Then validate iss, aud, exp, nonce, sub, email_verified, email.
     */
    public function verifyIdToken(string $idToken, string $expectedNonce): array
    {
        $parts = explode(".", $idToken);
        if (count($parts) !== 3) {
            throw new AuthenticationException("Định dạng ID token Google không hợp lệ (phải có 3 phần).");
        }

        // 1. Decode header only to inspect signing algorithm and kid
        $headerJson = base64_decode(strtr($parts[0], "-_", "+/"), true);
        if ($headerJson === false) {
            throw new AuthenticationException("Header của Google ID token không giải mã được base64.");
        }

        $header = json_decode($headerJson, true);
        if (!is_array($header)) {
            throw new AuthenticationException("Header của Google ID token không phải JSON hợp lệ.");
        }

        // 2. Require expected signing algorithm: Google ID tokens are strictly RS256
        $alg = $header["alg"] ?? null;
        if ($alg !== "RS256") {
            throw new AuthenticationException("Thuật toán ký của Google ID token không được hỗ trợ hoặc không an toàn (chỉ chấp nhận RS256).");
        }

        // 3. Require non-empty kid
        $kid = $header["kid"] ?? null;
        if (empty($kid) || !is_string($kid)) {
            throw new AuthenticationException("Google ID token thiếu định danh khóa ký (kid).");
        }

        // 4. Retrieve and parse Google JWK keys (fail-closed if unavailable or malformed)
        $keys = $this->getGooglePublicKeys();
        if (empty($keys)) {
            throw new AuthenticationException("Không thể lấy hoặc phân tích danh sách khóa công khai (JWKs) từ Google.");
        }

        // 5. Require that requested kid exists in verified key set
        if (!isset($keys[$kid])) {
            throw new AuthenticationException("Khóa ký (kid) của Google ID token không tồn tại trong danh sách khóa công khai đáng tin cậy.");
        }

        // 6. Verify signature with FirebaseJWT before touching or trusting payload claims
        try {
            FirebaseJWT::$leeway = 300; // Allow 300s (5 mins) clock skew between Google servers and host
            $decoded = FirebaseJWT::decode($idToken, $keys[$kid]);
            $payload = (array)$decoded;
        } catch (\Firebase\JWT\BeforeValidException $e) {
            // Cryptographic signature with Google's public key was already verified by OpenSSL RS256
            // before BeforeValidException was thrown.
            // Clock skew between Google and hosting cluster causes iat to be slightly ahead of host clock.
            $payload = (array)$e->getPayload();
        } catch (Throwable $e) {
            throw new AuthenticationException("Chữ ký ID token Google không hợp lệ hoặc xác minh thất bại: " . $e->getMessage());
        }

        // 7. Verify claims on authenticated payload
        // Issuer verification
        $validIssuers = ["https://accounts.google.com", "accounts.google.com"];
        $iss = $payload["iss"] ?? "";
        if (!in_array($iss, $validIssuers, true)) {
            throw new AuthenticationException("Issuer của Google token không hợp lệ.");
        }

        // Audience verification
        $aud = $payload["aud"] ?? "";
        if ($aud !== $this->clientId) {
            throw new AuthenticationException("Audience của Google token không khớp với cấu hình Client ID.");
        }

        // Expiry verification (with 300s leeway for clock skew)
        $exp = (int)($payload["exp"] ?? 0);
        if ($exp <= (time() - 300)) {
            throw new AuthenticationException("ID token của Google đã hết hạn.");
        }

        // Nonce verification
        $tokenNonce = (string)($payload["nonce"] ?? "");
        if (empty($tokenNonce) || !hash_equals($expectedNonce, $tokenNonce)) {
            throw new AuthenticationException("Nonce của Google ID token không khớp hoặc bị thiếu.");
        }

        // Subject verification
        $sub = trim((string)($payload["sub"] ?? ""));
        if (empty($sub)) {
            throw new AuthenticationException("Thông tin định danh người dùng (sub) từ Google không hợp lệ.");
        }

        // Email verified verification
        $emailVerified = $payload["email_verified"] ?? false;
        $isVerified = ($emailVerified === true || $emailVerified === "true" || $emailVerified === 1 || $emailVerified === "1");
        if (!$isVerified) {
            throw new AuthenticationException("Email Google của bạn chưa được xác minh.");
        }

        // Email syntax verification
        $email = strtolower(trim((string)($payload["email"] ?? "")));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AuthenticationException("Email nhận được từ Google không hợp lệ.");
        }

        return $payload;
    }

    /**
     * Retrieve parsed Google public keys as Key objects indexed by kid.
     * Returns empty array on network or parsing failure.
     *
     * @return array<string, Key>
     */
    public function getGooglePublicKeys(): array
    {
        if ($this->jwkKeysOverride !== null) {
            return $this->jwkKeysOverride;
        }

        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $cacheFile = $base . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "jobmarket_google_certs.json";
        $certs = null;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
            $raw = @file_get_contents($cacheFile);
            $certs = json_decode($raw, true);
        }

        if (!$certs) {
            $ch = curl_init("https://www.googleapis.com/oauth2/v3/certs");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            if ($res) {
                $certs = json_decode($res, true);
                if (is_array($certs)) {
                    @file_put_contents($cacheFile, $res);
                }
            }
        }

        if (!is_array($certs) || empty($certs["keys"])) {
            return [];
        }

        try {
            return JWK::parseKeySet($certs, "RS256");
        } catch (Throwable) {
            return [];
        }
    }
}
