<?php

define("BASE_PATH", __DIR__);

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use JobMarket\Database\Seeder;
use JobMarket\Facades\Config;

$config = Config::env();
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, "--dbname=")) {
        $config["dbname"] = substr($arg, 9);
        $_ENV["DB_NAME"] = $config["dbname"];
    }
}

$db = new PDO(
    "mysql:dbname={$config['dbname']};host={$config['host']}",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$force = in_array("--force", $argv ?? [], true);

try {
    $seeder = new Seeder($db);
    $seeder->run($force);
} catch (\Throwable $e) {
    echo "LỖI SEEDING: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
