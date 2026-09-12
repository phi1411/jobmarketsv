<?php

namespace JobMarket\Infrastructure;

use JobMarket\Facades\Config;
use PDO;

class JobAlertRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
            return;
        }
        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function enabledSavedSearches(): array
    {
        $stmt = $this->db->query(
            "SELECT ss.*, u.name AS user_name, u.email AS user_email,
                    cat.name AS category_name, loc.name AS location_name
             FROM saved_searches ss
             JOIN users u ON u.id = ss.user_id
             LEFT JOIN categories cat ON cat.id = ss.category_id
             LEFT JOIN locations loc ON loc.id = ss.location_id
             WHERE ss.notification_enabled = 1
               AND u.role IN ('student', 'developer')
               AND (u.status IS NULL OR u.status = 'active')"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reserve(array $search, string $jobId, int $score, array $details): ?string
    {
        $id = "alert-" . uniqid();
        $status = !empty($search["email_enabled"]) ? "pending" : "disabled";
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO saved_search_job_alerts
             (id, saved_search_id, job_id, user_id, match_score, match_details, email_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $id,
            $search["id"],
            $jobId,
            $search["user_id"],
            $score,
            json_encode($details, JSON_UNESCAPED_UNICODE),
            $status,
        ]);
        return $stmt->rowCount() > 0 ? $id : null;
    }

    public function attachNotification(string $alertId, string $notificationId): void
    {
        $stmt = $this->db->prepare("UPDATE saved_search_job_alerts SET notification_id = ? WHERE id = ?");
        $stmt->execute([$notificationId, $alertId]);
    }

    public function updateEmailResult(string $alertId, array $result): void
    {
        $status = in_array($result["status"] ?? "", ["sent", "failed", "preview"], true)
            ? $result["status"]
            : "failed";
        $stmt = $this->db->prepare(
            "UPDATE saved_search_job_alerts
             SET email_status = ?, email_sent_at = IF(? = 'sent', NOW(), email_sent_at), email_error = ?
             WHERE id = ?"
        );
        $stmt->execute([$status, $status, $result["error"] ?? null, $alertId]);
    }

    public function touchSearch(string $searchId): void
    {
        $stmt = $this->db->prepare("UPDATE saved_searches SET last_matched_at = NOW() WHERE id = ?");
        $stmt->execute([$searchId]);
    }

    public function pendingForFrequency(string $frequency): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.id AS alert_id, a.saved_search_id, a.match_score, a.match_details,
                    ss.name AS search_name, ss.frequency, u.name AS user_name, u.email AS user_email,
                    j.*, c.name AS company_name
             FROM saved_search_job_alerts a
             JOIN saved_searches ss ON ss.id = a.saved_search_id
             JOIN users u ON u.id = a.user_id
             JOIN jobs j ON j.id = a.job_id
             LEFT JOIN companies c ON c.id = j.company_id
             WHERE a.email_status = 'pending' AND ss.email_enabled = 1 AND ss.frequency = ?
               AND j.status = 'published' AND j.deleted_at IS NULL
             ORDER BY a.created_at ASC LIMIT 500"
        );
        $stmt->execute([$frequency]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
