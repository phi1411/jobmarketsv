<?php

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$frequency = $argv[1] ?? "daily";
if (!in_array($frequency, ["daily", "weekly"], true)) {
    fwrite(STDERR, "Usage: php dispatch_job_alerts.php [daily|weekly]" . PHP_EOL);
    exit(1);
}

try {
    $result = (new JobMarket\Domain\JobAlertService())->dispatchPendingEmails($frequency);
    if ($frequency === "daily") {
        $result["favorite_deadline_reminders"] = (new JobMarket\Domain\FavoriteDeadlineReminderService())->dispatch(false);
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
