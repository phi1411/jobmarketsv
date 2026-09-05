<?php

namespace JobMarket\Support;

class Logger
{
    private static string $logDir = BASE_PATH . "/storage/logs";
    private static string $logFile = BASE_PATH . "/storage/logs/app.log";

    public static function log(string $level, string $message, array $context = []): void
    {
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0777, true);
        }

        $sanitizedContext = self::sanitize($context);
        $timestamp = date("Y-m-d H:i:s");
        $contextString = !empty($sanitizedContext) ? " " . json_encode($sanitizedContext, JSON_UNESCAPED_UNICODE) : "";
        $logEntry = sprintf("[%s] [%s]: %s%s%s", $timestamp, strtoupper($level), $message, $contextString, PHP_EOL);

        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log("ERROR", $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log("INFO", $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log("WARNING", $message, $context);
    }

    public static function sanitize(array $data): array
    {
        $sensitiveKeys = ["password", "password_confirmation", "token", "secret", "authorization", "db_password", "jwt_secret", "api_key", "private_key", "employer_note"];
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string)$key), $sensitiveKeys, true)) {
                $sanitized[$key] = "********";
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
