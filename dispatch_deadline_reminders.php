<?php

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
$dryRun = in_array("--dry-run", $argv, true);

try {
    $result = (new JobMarket\Domain\FavoriteDeadlineReminderService())->dispatch($dryRun);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
