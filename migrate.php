<?php

define("BASE_PATH", __DIR__);

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

// 1. Create migrations tracking table if not exists
$db->exec(
    "CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `batch` INT NOT NULL,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
);

// Check for rollback option
$isRollback = in_array("--rollback", $argv ?? [], true);

if ($isRollback) {
    $batchStmt = $db->query("SELECT MAX(batch) FROM `migrations`");
    $lastBatch = (int)$batchStmt->fetchColumn();

    if ($lastBatch === 0) {
        echo "Nothing to rollback. No migrations have been executed." . PHP_EOL;
        exit(0);
    }

    $stmt = $db->prepare("SELECT `migration` FROM `migrations` WHERE `batch` = ? ORDER BY `id` DESC");
    $stmt->execute([$lastBatch]);
    $batchMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $deleteStmt = $db->prepare("DELETE FROM `migrations` WHERE `migration` = ?");

    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

    foreach ($batchMigrations as $migration) {
        if (class_exists($migration)) {
            $instance = new $migration();
            if (method_exists($instance, "down")) {
                $instance->down($db);
            } elseif (method_exists($instance, "getTableName")) {
                $table = $instance->getTableName();
                $db->exec("DROP TABLE IF EXISTS `{$table}`;");
            }
        }
        $deleteStmt->execute([$migration]);
        echo "  [ROLLED BACK] " . $migration . PHP_EOL;
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Rollback batch {$lastBatch} completed successfully." . PHP_EOL;
    exit(0);
}

// 2. Fetch already executed migrations
$stmt = $db->query("SELECT migration FROM `migrations`");
$executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate next batch
$batchStmt = $db->query("SELECT MAX(batch) FROM `migrations`");
$nextBatch = ((int)$batchStmt->fetchColumn()) + 1;

$migrations = [
    \JobMarket\Migrations\SkillMigration::class,
    \JobMarket\Migrations\UserMigration::class,
    \JobMarket\Migrations\CompanyMigration::class,
    \JobMarket\Migrations\JobMigration::class,
    \JobMarket\Migrations\ApplicationMigration::class,
    \JobMarket\Migrations\JobSkillMigration::class,
    \JobMarket\Migrations\FavoriteMigration::class,
    \JobMarket\Migrations\SearchMigration::class,
    \JobMarket\Migrations\CategoryMigration::class,
    \JobMarket\Migrations\LocationMigration::class,
    \JobMarket\Migrations\ReviewMigration::class,
    \JobMarket\Migrations\NotificationMigration::class,
    \JobMarket\Migrations\SubscriptionMigration::class,
    \JobMarket\Migrations\PaymentMigration::class,
    \JobMarket\Migrations\ReportMigration::class,
    \JobMarket\Migrations\AdminUserMigration::class,
    \JobMarket\Migrations\QualificationMigration::class,
    \JobMarket\Migrations\ExperienceMigration::class,
    \JobMarket\Migrations\ConversationMigration::class,
    \JobMarket\Migrations\MessageMigration::class,
    \JobMarket\Migrations\CategoryJobMigration::class,
    \JobMarket\Migrations\ReviewDeveloperMigration::class,
    \JobMarket\Migrations\PartTimeMarketplaceMigration::class,
    \JobMarket\Migrations\JobPostingEnhancementMigration::class,
    \JobMarket\Migrations\StudentProfileEnhancementMigration::class,
    \JobMarket\Migrations\JobApplicationEnhancementMigration::class,
    \JobMarket\Migrations\JobSearchFavoritesSavedSearchesMigration::class,
    \JobMarket\Migrations\NotificationDashboardEnhancementMigration::class,
    \JobMarket\Migrations\AdminModerationAuditMigration::class,
    \JobMarket\Migrations\OAuthIdentityMigration::class,
    \JobMarket\Migrations\StudentCvUploadMigration::class,
    \JobMarket\Migrations\ApplicationCvSnapshotMigration::class
];

$ranCount = 0;
$insertStmt = $db->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (?, ?)");

foreach ($migrations as $migration) {
    if (in_array($migration, $executedMigrations, true)) {
        continue;
    }

    $table = new $migration();
    $table->create();

    $insertStmt->execute([$migration, $nextBatch]);
    echo "  [MIGRATED] " . $migration . " (" . $table->getTableName() . ")" . PHP_EOL;
    $ranCount++;
}

if ($ranCount === 0) {
    echo "Nothing to migrate. All migrations are up to date." . PHP_EOL;
} else {
    echo "Migration completed successfully: {$ranCount} migrations executed in batch {$nextBatch}." . PHP_EOL;
}
