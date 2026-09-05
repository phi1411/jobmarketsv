<?php

namespace JobMarket\Facades;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Throwable;

class JWT extends Facade
{
    public static function encode(array $payload, int $ttl = 2592000): string
    {
        $now = time();
        $tokenData = [
            "iss"   => "JobMarketplace",
            "iat"   => $now,
            "exp"   => $now + $ttl,
            "id"    => $payload["id"] ?? null,
            "email" => $payload["email"] ?? "",
            "role"  => $payload["role"] ?? "student"
        ];

        return FirebaseJWT::encode($tokenData, Config::secret(), "HS256");
    }

    public static function decode(string $token): ?array
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key(Config::secret(), "HS256"));
            return (array)$decoded;
        } catch (Throwable $e) {
            return null;
        }
    }
}
