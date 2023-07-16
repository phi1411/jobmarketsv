<?php

namespace JobMarket\Migrations;

use JobMarket\Facades\Config;
use PDO;

class Migration
{
    private PDO $db;
    public function __construct()
    {
        if (!isset($this->db)) {
            $config = Config::env();

            $this->db = new PDO(
                "mysql:dbname={$config['dbname']};host={$config['host']}",
                $config["user"],
                $config["password"]
            );
        }
    }

    public function getDB(): PDO
    {
        return $this->db;
    }
}
