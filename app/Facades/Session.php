<?php

namespace JobMarket\Facades;

class Session extends Facade
{
    public static function token(): ?string
    {
        $authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) $bearerToken = $matches[1];
        else $bearerToken = null;

        return $bearerToken;
    }
}
