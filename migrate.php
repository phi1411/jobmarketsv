<?php

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
    \JobMarket\Migrations\ReviewDeveloperMigration::class
];


foreach($migrations as $migration){
    $table = new $migration();

    $table->create();

    echo $table->getTableName() . " created ..." . PHP_EOL;
}
