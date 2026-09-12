<?php

namespace JobMarket\Domain;

use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\CompanyRepository;
use PDO;

class DashboardService
{
    private PDO $db;
    private CompanyRepository $companyRepo;

    public function __construct(?CompanyRepository $companyRepo = null)
    {
        $this->companyRepo = $companyRepo ?? new CompanyRepository();

        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * Student Dashboard: application counts, expiring favorites, unread notif, recent notifs
     */
    public function studentDashboard(array $user): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền truy cập Student Dashboard.");
        }

        $userId = $user["id"] ?? "";

        // 1. Application count by status
        $stmtApp = $this->db->prepare(
            "SELECT `status`, COUNT(*) as `count` FROM `applications` WHERE `developer_id` = ? GROUP BY `status`"
        );
        $stmtApp->execute([$userId]);
        $appRows = $stmtApp->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $appCounts = [
            "pending"     => (int)($appRows["pending"] ?? 0),
            "viewed"      => (int)($appRows["viewed"] ?? 0),
            "shortlisted" => (int)($appRows["shortlisted"] ?? 0),
            "accepted"    => (int)($appRows["accepted"] ?? 0),
            "rejected"    => (int)($appRows["rejected"] ?? 0),
            "withdrawn"   => (int)($appRows["withdrawn"] ?? 0),
            "total"       => array_sum($appRows)
        ];

        // 2. Expiring favorite jobs (deadline >= CURDATE() ordered by deadline ascending)
        $stmtFav = $this->db->prepare(
            "SELECT j.id, j.title, j.application_deadline, 
                    c.name as company_name, c.logo_url as company_logo,
                    j.salary_min, j.salary_max, j.shift_type, j.location, j.city
             FROM `favorites` f
             JOIN `jobs` j ON f.job_id = j.id
             LEFT JOIN `companies` c ON j.company_id = c.id
             WHERE f.user_id = ? 
               AND j.deleted_at IS NULL 
               AND j.status = 'published'
               AND (j.application_deadline IS NOT NULL AND j.application_deadline >= CURDATE())
             ORDER BY j.application_deadline ASC
             LIMIT 5"
        );
        $stmtFav->execute([$userId]);
        $expiringFavorites = $stmtFav->fetchAll(PDO::FETCH_ASSOC);

        // 3. Unread notification count
        $stmtUnread = $this->db->prepare(
            "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `read_at` IS NULL"
        );
        $stmtUnread->execute([$userId]);
        $unreadCount = (int)$stmtUnread->fetchColumn();

        // 4. Recent notifications
        $stmtNotif = $this->db->prepare(
            "SELECT `id`, `type`, `title`, `message`, `data`, `read_at`, `created_at` 
             FROM `notifications` 
             WHERE `user_id` = ? 
             ORDER BY `created_at` DESC 
             LIMIT 5"
        );
        $stmtNotif->execute([$userId]);
        $rawNotifs = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        $recentNotifications = array_map(function($n) {
            $data = $n["data"];
            if (is_string($data)) {
                $decoded = json_decode($data, true);
                $n["data"] = is_array($decoded) ? $decoded : null;
            }
            $n["is_read"] = $n["read_at"] !== null;
            return $n;
        }, $rawNotifs);

        return [
            "applications"          => $appCounts,
            "expiring_favorites"    => $expiringFavorites,
            "unread_notifications"  => $unreadCount,
            "recent_notifications"  => $recentNotifications
        ];
    }

    /**
     * Company Dashboard: job counts, application counts, recent applications, top jobs
     */
    public function companyDashboard(array $user): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền truy cập Company Dashboard.");
        }

        $company = $this->companyRepo->findByUserId($user["id"]);
        if (!$company) {
            throw new AuthorizationException("Không tìm thấy thông tin công ty.");
        }

        $companyId = $company["id"];

        // 1. Job counts by status
        $stmtJob = $this->db->prepare(
            "SELECT `status`, COUNT(*) as `count` FROM `jobs` WHERE `company_id` = ? AND `deleted_at` IS NULL GROUP BY `status`"
        );
        $stmtJob->execute([$companyId]);
        $jobRows = $stmtJob->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $jobCounts = [
            "published"        => (int)($jobRows["published"] ?? 0),
            "draft"            => (int)($jobRows["draft"] ?? 0),
            "closed"           => (int)($jobRows["closed"] ?? 0),
            "pending_approval" => (int)($jobRows["pending_approval"] ?? 0),
            "total"            => array_sum($jobRows)
        ];

        // 2. Application count by status for company jobs
        $stmtApp = $this->db->prepare(
            "SELECT a.status, COUNT(*) as `count`
             FROM `applications` a
             JOIN `jobs` j ON a.job_id = j.id
             WHERE j.company_id = ?
             GROUP BY a.status"
        );
        $stmtApp->execute([$companyId]);
        $appRows = $stmtApp->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $appCounts = [
            "pending"     => (int)($appRows["pending"] ?? 0),
            "viewed"      => (int)($appRows["viewed"] ?? 0),
            "shortlisted" => (int)($appRows["shortlisted"] ?? 0),
            "accepted"    => (int)($appRows["accepted"] ?? 0),
            "rejected"    => (int)($appRows["rejected"] ?? 0),
            "withdrawn"   => (int)($appRows["withdrawn"] ?? 0),
            "total"       => array_sum($appRows)
        ];

        // 3. Recent applications (Safe, no private fields leak)
        $stmtRecent = $this->db->prepare(
            "SELECT a.id, a.job_id, j.title as job_title, a.applied_at, a.status,
                    u.name as student_name, sp.university, sp.major
             FROM `applications` a
             JOIN `jobs` j ON a.job_id = j.id
             JOIN `users` u ON a.developer_id = u.id
             LEFT JOIN `student_profiles` sp ON u.id = sp.user_id
             WHERE j.company_id = ?
             ORDER BY a.applied_at DESC
             LIMIT 5"
        );
        $stmtRecent->execute([$companyId]);
        $recentApplications = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

        // 4. Top jobs by application count
        $stmtTop = $this->db->prepare(
            "SELECT j.id, j.title, j.status, COUNT(a.id) as `application_count`
             FROM `jobs` j
             LEFT JOIN `applications` a ON j.id = a.job_id
             WHERE j.company_id = ? AND j.deleted_at IS NULL
             GROUP BY j.id
             ORDER BY `application_count` DESC
             LIMIT 5"
        );
        $stmtTop->execute([$companyId]);
        $topJobs = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        // 5. Unread notifications
        $stmtUnread = $this->db->prepare(
            "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `read_at` IS NULL"
        );
        $stmtUnread->execute([$user["id"]]);
        $unreadCount = (int)$stmtUnread->fetchColumn();

        return [
            "company_name"         => $company["name"],
            "jobs"                 => $jobCounts,
            "applications"         => $appCounts,
            "recent_applications"  => $recentApplications,
            "top_jobs"             => $topJobs,
            "unread_notifications" => $unreadCount
        ];
    }

    /**
     * Admin Dashboard: total users, companies, jobs, applications
     */
    public function adminDashboard(array $user): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "admin") {
            throw new AuthorizationException("Chỉ Quản trị viên mới có quyền truy cập Admin Dashboard.");
        }

        // 1. Users by role
        $stmtUsers = $this->db->query("SELECT `role`, COUNT(*) as `count` FROM `users` GROUP BY `role`");
        $userRows = $stmtUsers->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // 2. Companies by verification status
        $stmtComp = $this->db->query("SELECT `verification_status`, COUNT(*) as `count` FROM `companies` GROUP BY `verification_status`");
        $compRows = $stmtComp->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // 3. Jobs by status
        $stmtJobs = $this->db->query("SELECT `status`, COUNT(*) as `count` FROM `jobs` WHERE `deleted_at` IS NULL GROUP BY `status`");
        $jobRows = $stmtJobs->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // 4. Applications by status
        $stmtApps = $this->db->query("SELECT `status`, COUNT(*) as `count` FROM `applications` GROUP BY `status`");
        $appRows = $stmtApps->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        return [
            "users" => [
                "admin"   => (int)($userRows["admin"] ?? 0),
                "company" => (int)($userRows["company"] ?? 0),
                "student" => (int)($userRows["student"] ?? ($userRows["developer"] ?? 0)),
                "total"   => array_sum($userRows)
            ],
            "companies" => [
                "verified"   => (int)($compRows["verified"] ?? 0),
                "pending"    => (int)($compRows["pending"] ?? 0),
                "unverified" => (int)($compRows["unverified"] ?? 0),
                "total"      => array_sum($compRows)
            ],
            "jobs" => [
                "published" => (int)($jobRows["published"] ?? 0),
                "draft"     => (int)($jobRows["draft"] ?? 0),
                "closed"    => (int)($jobRows["closed"] ?? 0),
                "total"     => array_sum($jobRows)
            ],
            "applications" => [
                "pending"     => (int)($appRows["pending"] ?? 0),
                "viewed"      => (int)($appRows["viewed"] ?? 0),
                "shortlisted" => (int)($appRows["shortlisted"] ?? 0),
                "accepted"    => (int)($appRows["accepted"] ?? 0),
                "rejected"    => (int)($appRows["rejected"] ?? 0),
                "withdrawn"   => (int)($appRows["withdrawn"] ?? 0),
                "total"       => array_sum($appRows)
            ]
        ];
    }
}
