<?php

namespace JobMarket\Facades;

class Config extends Facade
{
    public static function env(): array
    {
        return [
            "dbname"   => $_ENV["DB_NAME"],
            "password" => $_ENV["DB_PASSWORD"],
            "user"     => $_ENV["DB_USER"],
            "host"     => $_ENV["DB_HOST"]
        ];
    }
}
