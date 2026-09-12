<?php

namespace JobMarket\Infrastructure;

use JobMarket\Facades\Config;
use PDO;

class ProfileJobAlertRepository
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

    public function enabledProfilesForJob(string $jobId): array
    {
        $stmt = $this->db->prepare(
            "SELECT sp.*, u.name AS user_name, u.email AS user_email
             FROM student_profiles sp
             JOIN users u ON u.id = sp.user_id
             WHERE sp.recommendation_alert_enabled = 1
               AND u.role IN ('student', 'developer')
               AND (u.status IS NULL OR u.status = 'active')
               AND NOT EXISTS (
                   SELECT 1 FROM applications a
                   WHERE a.developer_id = sp.user_id AND a.job_id = ?
               )"
        );
        $stmt->execute([$jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSettings(string $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT recommendation_alert_enabled, recommendation_email_enabled, recommendation_minimum_score
             FROM student_profiles WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            "enabled" => (bool)($row["recommendation_alert_enabled"] ?? false),
            "email_enabled" => (bool)($row["recommendation_email_enabled"] ?? false),
            "minimum_score" => (int)($row["recommendation_minimum_score"] ?? 65),
        ];
    }

    public function updateSettings(string $userId, bool $enabled, bool $emailEnabled, int $minimumScore): array
    {
        $stmt = $this->db->prepare(
            "UPDATE student_profiles
             SET recommendation_alert_enabled = ?, recommendation_email_enabled = ?, recommendation_minimum_score = ?, updated_at = NOW()
             WHERE user_id = ?"
        );
        $stmt->execute([$enabled ? 1 : 0, ($enabled && $emailEnabled) ? 1 : 0, $minimumScore, $userId]);
        return $this->getSettings($userId);
    }

    public function reserve(array $profile, string $jobId, int $score, int $coverage, array $details): ?string
    {
        $id = "profile-alert-" . uniqid();
        $emailStatus = !empty($profile["recommendation_email_enabled"]) ? "pending" : "disabled";
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO profile_job_alerts
             (id, user_id, job_id, match_score, coverage_percent, match_details, email_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $id,
            $profile["user_id"],
            $jobId,
            $score,
            $coverage,
            json_encode($details, JSON_UNESCAPED_UNICODE),
            $emailStatus,
        ]);
        return $stmt->rowCount() > 0 ? $id : null;
    }

    public function attachNotification(string $alertId, string $notificationId): void
    {
        $stmt = $this->db->prepare("UPDATE profile_job_alerts SET notification_id = ? WHERE id = ?");
        $stmt->execute([$notificationId, $alertId]);
    }

    public function updateEmailResult(string $alertId, array $result): void
    {
        $status = in_array($result["status"] ?? "", ["sent", "failed", "preview"], true)
            ? $result["status"]
            : "failed";
        $stmt = $this->db->prepare(
            "UPDATE profile_job_alerts
             SET email_status = ?, email_sent_at = IF(? = 'sent', NOW(), email_sent_at), email_error = ?
             WHERE id = ?"
        );
        $stmt->execute([$status, $status, $result["error"] ?? null, $alertId]);
    }
}
