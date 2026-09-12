<?php

namespace JobMarket\Infrastructure;

use JobMarket\Facades\Config;
use PDO;

class FavoriteDeadlineReminderRepository
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

    public function candidates(int $withinDays = 2): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.user_id, f.job_id, j.title AS job_title, j.application_deadline,
                    DATEDIFF(j.application_deadline, CURDATE()) AS days_remaining
             FROM `favorites` f
             JOIN `users` u ON u.id = f.user_id AND u.status = 'active'
             JOIN `jobs` j ON j.id = f.job_id AND j.status = 'published' AND j.deleted_at IS NULL
             LEFT JOIN `applications` a ON a.job_id = f.job_id AND a.developer_id = f.user_id
             LEFT JOIN `favorite_deadline_reminders` r
                    ON r.user_id = f.user_id AND r.job_id = f.job_id AND r.deadline = j.application_deadline
             WHERE j.application_deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND a.id IS NULL AND r.id IS NULL
             ORDER BY j.application_deadline ASC"
        );
        $stmt->execute([$withinDays]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createReminder(array $candidate): bool
    {
        $notificationId = "notif-" . bin2hex(random_bytes(12));
        $reminderId = "drem-" . bin2hex(random_bytes(12));
        $days = max(0, (int)$candidate["days_remaining"]);
        $timeText = $days === 0 ? "hết hạn hôm nay" : "còn {$days} ngày để ứng tuyển";
        $data = json_encode([
            "job_id" => $candidate["job_id"],
            "job_title" => $candidate["job_title"],
            "deadline" => $candidate["application_deadline"],
            "days_remaining" => $days,
            "url" => "/viec-lam/" . $candidate["job_id"],
        ], JSON_UNESCAPED_UNICODE);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO `notifications` (`id`,`user_id`,`type`,`title`,`message`,`data`,`is_read`,`created_at`)
                 VALUES (?,?,'favorite_deadline_reminder','Tin đã lưu sắp hết hạn',?,?,0,NOW())"
            );
            $stmt->execute([
                $notificationId, $candidate["user_id"],
                "Tin '{$candidate['job_title']}' bạn đã lưu {$timeText}.", $data,
            ]);
            $stmt = $this->db->prepare(
                "INSERT INTO `favorite_deadline_reminders` (`id`,`user_id`,`job_id`,`deadline`,`days_remaining`,`notification_id`)
                 VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([$reminderId, $candidate["user_id"], $candidate["job_id"], $candidate["application_deadline"], $days, $notificationId]);
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            if ((string)$e->getCode() === "23000") return false;
            throw $e;
        }
    }
}
