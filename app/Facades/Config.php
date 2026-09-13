<?php

namespace JobMarket\Facades;

class Config extends Facade
{
    private static function getEnv(string $key, mixed $default = null): mixed
    {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($val !== false && $val !== null && $val !== "") ? $val : $default;
    }

    public static function env(): array
    {
        return [
            "dbname"   => self::getEnv("DB_NAME", "jobmarket"),
            "password" => self::getEnv("DB_PASSWORD", ""),
            "user"     => self::getEnv("DB_USER", "root"),
            "host"     => self::getEnv("DB_HOST", "localhost"),
            "port"     => self::getEnv("DB_PORT", "3306")
        ];
    }

    public static function secret(): string
    {
        return self::getEnv("SECRET", "default_secret_key_at_least_32_characters_long_123456");
    }

    public static function isProduction(): bool
    {
        return self::getEnv("APP_ENV") === "production";
    }

    public static function isDebug(): bool
    {
        return self::getEnv("APP_DEBUG", "false") === "true";
    }

    public static function geminiApiKey(): ?string
    {
        $key = self::getEnv("GEMINI_API_KEY");
        return (!empty($key) && is_string($key)) ? trim($key) : null;
    }

    public static function geminiModel(): ?string
    {
        $model = self::getEnv("GEMINI_MODEL");
        return (!empty($model) && is_string($model)) ? trim($model) : null;
    }

    public static function isGeminiEnabled(): bool
    {
        $enabled = self::getEnv("GEMINI_FEATURE_ENABLED");
        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isGeminiCompanyEnabled(): bool
    {
        if (!self::isGeminiEnabled()) {
            return false;
        }
        $enabled = self::getEnv("GEMINI_COMPANY_ENABLED");
        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    public static function geminiTimeout(): int
    {
        $timeout = self::getEnv("GEMINI_TIMEOUT_SECONDS");
        $val = filter_var($timeout, FILTER_VALIDATE_INT);
        return ($val !== false && $val > 0) ? $val : 15;
    }

    public static function chatGuestRateLimit(): int
    {
        $limit = self::getEnv("CHAT_GUEST_RATE_LIMIT");
        $val = filter_var($limit, FILTER_VALIDATE_INT);
        return ($val !== false && $val > 0) ? $val : 10;
    }

    public static function chatAuthRateLimit(): int
    {
        $limit = self::getEnv("CHAT_AUTH_RATE_LIMIT");
        $val = filter_var($limit, FILTER_VALIDATE_INT);
        return ($val !== false && $val > 0) ? $val : 30;
    }

    /**
     * @return array<int, string>
     */
    public static function trustedProxies(): array
    {
        $raw = self::getEnv("TRUSTED_PROXIES", "");
        if (trim((string)$raw) === "") {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', (string)$raw)), fn($ip) => $ip !== ""));
    }

    public static function aiMatchHashKey(): ?string
    {
        $key = self::getEnv("AI_MATCH_HASH_KEY");
        return (!empty($key) && is_string($key)) ? trim($key) : null;
    }

    public static function aiMatchRateLimit(): int
    {
        $value = filter_var(self::getEnv("AI_MATCH_RATE_LIMIT"), FILTER_VALIDATE_INT);
        return ($value !== false && $value >= 1 && $value <= 100) ? $value : 5;
    }

    public static function aiMatchRateDecaySeconds(): int
    {
        $value = filter_var(self::getEnv("AI_MATCH_RATE_DECAY_SECONDS"), FILTER_VALIDATE_INT);
        return ($value !== false && $value >= 10 && $value <= 3600) ? $value : 60;
    }

    public static function appUrl(): string
    {
        return rtrim((string)self::getEnv("APP_URL", "http://localhost"), "/");
    }

    public static function jobAlertMatchThreshold(): int
    {
        $value = filter_var(self::getEnv("JOB_ALERT_MATCH_THRESHOLD"), FILTER_VALIDATE_INT);
        return ($value !== false && $value >= 30 && $value <= 100) ? $value : 65;
    }

    public static function mail(): array
    {
        return [
            "host"       => trim((string)self::getEnv("MAIL_HOST", "")),
            "port"       => (int)self::getEnv("MAIL_PORT", 587),
            "username"   => trim((string)self::getEnv("MAIL_USERNAME", "")),
            "password"   => (string)self::getEnv("MAIL_PASSWORD", ""),
            "encryption" => strtolower(trim((string)self::getEnv("MAIL_ENCRYPTION", "tls"))),
            "from"       => trim((string)self::getEnv("MAIL_FROM_ADDRESS", "no-reply@jobmarketsv.local")),
            "from_name"  => trim((string)self::getEnv("MAIL_FROM_NAME", "JobMarketSV")),
        ];
    }

    public static function isMailConfigured(): bool
    {
        $mail = self::mail();
        return $mail["host"] !== "" && $mail["username"] !== "" && $mail["password"] !== "";
    }

    public static function goongRestApiKey(): ?string
    {
        $key = self::getEnv("GOONG_REST_API_KEY");
        return is_string($key) && trim($key) !== "" ? trim($key) : null;
    }

    public static function goongApiBaseUrl(): string
    {
        return rtrim((string)self::getEnv("GOONG_API_BASE_URL", "https://rsapi.goong.io"), "/");
    }

    public static function goongTimeoutSeconds(): int
    {
        $value = filter_var(self::getEnv("GOONG_TIMEOUT_SECONDS"), FILTER_VALIDATE_INT);
        return ($value !== false && $value >= 2 && $value <= 30) ? $value : 8;
    }

    public static function locationApiRateLimit(): int
    {
        $value = filter_var(self::getEnv("LOCATION_API_RATE_LIMIT"), FILTER_VALIDATE_INT);
        return ($value !== false && $value >= 5 && $value <= 300) ? $value : 60;
    }
}
