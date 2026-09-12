<?php

namespace JobMarket\Facades;

class Session extends Facade
{
    public static function token(): ?string
    {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (empty($authorizationHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authorizationHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (empty($authorizationHeader) && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authorizationHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        if (!empty($_SERVER['HTTP_X_TOKEN'])) {
            return trim($_SERVER['HTTP_X_TOKEN']);
        }

        if (!empty($_COOKIE['token'])) {
            return trim($_COOKIE['token']);
        }

        return null;
    }
}
