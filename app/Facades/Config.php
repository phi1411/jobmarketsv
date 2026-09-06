<?php

namespace JobMarket\Facades;

class Config extends Facade
{
    public static function env(): array
    {
        return [
            "dbname"   => $_ENV["DB_NAME"] ?? "jobmarket",
            "password" => $_ENV["DB_PASSWORD"] ?? "",
            "user"     => $_ENV["DB_USER"] ?? "root",
            "host"     => $_ENV["DB_HOST"] ?? "localhost",
            "port"     => $_ENV["DB_PORT"] ?? "3306"
        ];
    }

    public static function secret(): string
    {
        return $_ENV["SECRET"] ?? "default_secret_key_at_least_32_characters_long_123456";
    }

    public static function isProduction(): bool
    {
        return ($_ENV["APP_ENV"] ?? "") === "production";
    }

    public static function isDebug(): bool
    {
        return ($_ENV["APP_DEBUG"] ?? "false") === "true";
    }

    public static function geminiApiKey(): ?string
    {
        $key = $_ENV["GEMINI_API_KEY"] ?? getenv("GEMINI_API_KEY");
        return (!empty($key) && is_string($key)) ? trim($key) : null;
    }

    public static function geminiModel(): ?string
    {
        $model = $_ENV["GEMINI_MODEL"] ?? getenv("GEMINI_MODEL");
        return (!empty($model) && is_string($model)) ? trim($model) : null;
    }

    public static function isGeminiEnabled(): bool
    {
        $enabled = $_ENV["GEMINI_FEATURE_ENABLED"] ?? getenv("GEMINI_FEATURE_ENABLED");
        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isGeminiCompanyEnabled(): bool
    {
        if (!self::isGeminiEnabled()) {
            return false;
        }
        $enabled = $_ENV["GEMINI_COMPANY_ENABLED"] ?? getenv("GEMINI_COMPANY_ENABLED");
        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    public static function geminiTimeout(): int
    {
        $timeout = $_ENV["GEMINI_TIMEOUT_SECONDS"] ?? getenv("GEMINI_TIMEOUT_SECONDS");
        $val = filter_var($timeout, FILTER_VALIDATE_INT);
        return ($val !== false && $val > 0) ? $val : 15;
    }

    public static function chatGuestRateLimit(): int
    {
        $limit = $_ENV["CHAT_GUEST_RATE_LIMIT"] ?? getenv("CHAT_GUEST_RATE_LIMIT");
        $val = filter_var($limit, FILTER_VALIDATE_INT);
        return ($val !== false && $val > 0) ? $val : 10;
    }

    public static function chatAuthRateLimit(): int
    {
        $limit = $_ENV["CHAT_AUTH_RATE_LIMIT"] ?? getenv("CHAT_AUTH_RATE_LIMIT");
        $val = filter_var($limit, FILTER_VALIDATE_INT);
        return ($val !== false && $val > 0) ? $val : 30;
    }

    /**
     * @return array<int, string>
     */
    public static function trustedProxies(): array
    {
        $raw = $_ENV["TRUSTED_PROXIES"] ?? getenv("TRUSTED_PROXIES") ?: "";
        if (trim($raw) === "") {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn($ip) => $ip !== ""));
    }
}
