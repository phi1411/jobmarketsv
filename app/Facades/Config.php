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
}
