<?php

namespace JobMarket\Infrastructure;

use JobMarket\Facades\Config;
use PDO;

class ApplicationDecisionDeliveryRepository
{
    private PDO $db;

    public function __construct()
    {
        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"], $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function create(string $applicationId, string $status, string $message, ?string $notificationId, array $emailResult): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `application_decision_deliveries`
             (`id`,`application_id`,`status`,`student_message`,`notification_id`,`email_status`,`email_error`)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            "adel-" . bin2hex(random_bytes(12)), $applicationId, $status, $message, $notificationId,
            $emailResult["status"] ?? "failed", $emailResult["error"] ?? null,
        ]);
    }
}
