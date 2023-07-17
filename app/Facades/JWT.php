<?php

namespace JobMarket\Facades;

class JWT extends Facade
{
    public static function encode(array $payload): string
    {
        $payload = [
            "email"    => $payload["email"],
            "password" => $payload["password"],
            "exp"   => time() + (1 * 30 * 24 * 3600)
        ];

        $secret = Config::secret();

        $jwt = \Firebase\JWT\JWT::encode($payload, $secret, "HS256");
        return $jwt;
    }
}
