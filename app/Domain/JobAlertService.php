<?php

namespace JobMarket\Domain;

use JobMarket\Facades\Config;
use JobMarket\Infrastructure\JobAlertRepository;
use JobMarket\Support\Logger;

class JobAlertService
{
    public function __construct(
        private ?JobAlertRepository $alerts = null,
        private ?NotificationService $notifications = null,
        private ?MailService $mail = null
    ) {
        $this->alerts ??= new JobAlertRepository();
        $this->notifications ??= new NotificationService();
        $this->mail ??= new MailService();
    }

    /**
     * Creates an immediate in-app alert and sends/queues email for each matching saved search.
     * A unique database key makes this safe to call more than once for the same job.
     */
    public function processPublishedJob(array $job): array
    {
        if (($job["status"] ?? "") !== "published") {
            return ["matched" => 0, "notified" => 0, "emailed" => 0];
        }

        $summary = ["matched" => 0, "notified" => 0, "emailed" => 0];
        foreach ($this->alerts->enabledSavedSearches() as $search) {
            $match = $this->calculateMatch($search, $job);
            $threshold = (int)($search["minimum_match_score"] ?? Config::jobAlertMatchThreshold());
            if ($match["score"] < $threshold || $match["active_criteria"] === 0) {
                continue;
            }

            $summary["matched"]++;
            $alertId = $this->alerts->reserve($search, (string)$job["id"], $match["score"], $match["details"]);
            if ($alertId === null) {
                continue;
            }

            try {
                $notification = $this->notifications->notify(
                    (string)$search["user_id"],
                    "Việc làm mới phù hợp {$match['score']}%",
                    "{$job['title']} tại " . ($job["company_name"] ?? "nhà tuyển dụng") . " phù hợp với bộ lọc '{$search['name']}'.",
                    "saved_search_job_match",
                    [
                        "job_id" => $job["id"],
                        "saved_search_id" => $search["id"],
                        "match_score" => $match["score"],
                        "match_details" => $match["details"],
                        "url" => "/viec-lam/" . rawurlencode((string)$job["id"]),
                    ]
                );
                $this->alerts->attachNotification($alertId, $notification->getId());
                $this->alerts->touchSearch((string)$search["id"]);
                $summary["notified"]++;
            } catch (\Throwable $e) {
                Logger::error("Không thể tạo thông báo việc làm phù hợp.", ["alert_id" => $alertId, "error" => $e->getMessage()]);
            }

            if (!empty($search["email_enabled"]) && ($search["frequency"] ?? "instant") === "instant") {
                $result = $this->mail->sendJobAlert(
                    ["name" => $search["user_name"], "email" => $search["user_email"]],
                    $job,
                    $search,
                    $match["score"],
                    $match["details"]
                );
                $this->alerts->updateEmailResult($alertId, $result);
                if (in_array($result["status"], ["sent", "preview"], true)) {
                    $summary["emailed"]++;
                }
            }
        }

        Logger::info("Đã xử lý thông báo tìm kiếm đã lưu cho tin mới.", ["job_id" => $job["id"] ?? null] + $summary);
        return $summary;
    }

    public function dispatchPendingEmails(string $frequency): array
    {
        if (!in_array($frequency, ["daily", "weekly"], true)) {
            throw new \InvalidArgumentException("Tần suất gửi phải là daily hoặc weekly.");
        }

        $summary = ["processed" => 0, "sent" => 0, "preview" => 0, "failed" => 0];
        foreach ($this->alerts->pendingForFrequency($frequency) as $row) {
            $details = json_decode((string)($row["match_details"] ?? "[]"), true);
            $details = is_array($details) ? $details : [];
            $search = ["id" => $row["saved_search_id"], "name" => $row["search_name"], "frequency" => $frequency];
            $result = $this->mail->sendJobAlert(
                ["name" => $row["user_name"], "email" => $row["user_email"]],
                $row,
                $search,
                (int)$row["match_score"],
                $details
            );
            $this->alerts->updateEmailResult((string)$row["alert_id"], $result);
            $summary["processed"]++;
            $summary[$result["status"]] = ($summary[$result["status"]] ?? 0) + 1;
        }
        return $summary;
    }

    /**
     * Score is normalized over only the criteria the student actually selected.
     * @return array{score:int,active_criteria:int,details:array}
     */
    public function calculateMatch(array $search, array $job): array
    {
        $criteria = [];
        $add = function (string $key, string $label, int $weight, float $value) use (&$criteria): void {
            $criteria[] = [
                "key" => $key,
                "label" => $label,
                "weight" => $weight,
                "value" => round(max(0, min(1, $value)), 3),
                "matched" => $value >= 0.6,
            ];
        };

        if (!empty($search["keyword"])) {
            $keyword = $this->normalize((string)$search["keyword"]);
            $title = $this->normalize((string)($job["title"] ?? ""));
            $body = $this->normalize(implode(" ", [
                $job["description"] ?? "", $job["requirements"] ?? "", $job["benefits"] ?? ""
            ]));
            $value = 0.0;
            if ($keyword !== "" && str_contains($title, $keyword)) {
                $value = 1.0;
            } elseif ($keyword !== "" && str_contains($body, $keyword)) {
                $value = 0.85;
            } else {
                $wanted = $this->tokens($keyword);
                $titleOverlap = $this->tokenOverlap($wanted, $this->tokens($title));
                $bodyOverlap = $this->tokenOverlap($wanted, $this->tokens($body));
                $value = max($titleOverlap, $bodyOverlap * 0.8);
            }
            $add("keyword", "Từ khóa '{$search['keyword']}'", 35, $value);
        }

        if (!empty($search["category_id"])) {
            $jobCategory = $job["category_id"] ?? ($job["category"] ?? null);
            $value = (string)$jobCategory === (string)$search["category_id"] ? 1.0 : 0.0;
            $add("category", "Đúng ngành " . ($search["category_name"] ?? "đã chọn"), 20, $value);
        }

        if (!empty($search["location_id"])) {
            $searchLocIds = array_filter(array_map('trim', explode(",", (string)$search["location_id"])));
            $jobLocId = (string)($job["location_id"] ?? ($job["location"] ?? ""));
            $value = 0.0;
            if ($jobLocId !== "" && in_array($jobLocId, $searchLocIds, true)) {
                $value = 1.0;
            }

            if ($value === 0.0) {
                $jobLocation = $this->normalize(implode(" ", [
                    $job["location"] ?? "",
                    $job["city"] ?? "",
                    $job["district"] ?? "",
                    $job["address"] ?? "",
                    $job["title"] ?? ""
                ]));

                if (!empty($search["location_name"]) && str_contains($jobLocation, $this->normalize((string)$search["location_name"]))) {
                    $value = 0.85;
                } elseif (!empty($job["work_locations"]) && is_array($job["work_locations"])) {
                    foreach ($job["work_locations"] as $wl) {
                        $wlText = $this->normalize(implode(" ", [
                            $wl["province"] ?? "",
                            $wl["commune"] ?? "",
                            $wl["address"] ?? "",
                            $wl["district"] ?? ""
                        ]));
                        if (!empty($search["location_name"]) && str_contains($wlText, $this->normalize((string)$search["location_name"]))) {
                            $value = 0.85;
                            break;
                        }
                    }
                }
            }
            $add("location", "Đúng khu vực " . ($search["location_name"] ?? "đã chọn"), 15, $value);
        }

        foreach (["work_type" => ["Hình thức làm việc", 10], "work_mode" => ["Cách thức làm việc", 5], "shift_type" => ["Ca làm việc", 8]] as $key => [$label, $weight]) {
            if (!empty($search[$key])) {
                $left = str_replace(["-", "_"], "", strtolower((string)$search[$key]));
                $right = str_replace(["-", "_"], "", strtolower((string)($job[$key] ?? "")));
                $add($key, $label . " phù hợp", $weight, $left === $right ? 1.0 : 0.0);
            }
        }

        if (isset($search["salary_min"]) && is_numeric($search["salary_min"])) {
            $wanted = (int)$search["salary_min"];
            $offered = max((int)($job["salary_min"] ?? 0), (int)($job["salary_max"] ?? 0));
            $value = $wanted <= 0 ? 1.0 : min(1.0, $offered / $wanted);
            $add("salary_min", "Mức lương đáp ứng mong muốn", 12, $value);
        }

        if (!empty($search["skill_ids"])) {
            $wantedSkills = is_array($search["skill_ids"])
                ? $search["skill_ids"]
                : (json_decode((string)$search["skill_ids"], true) ?: []);
            $jobSkills = $job["skills"] ?? ($job["required_skills"] ?? []);
            if (is_string($jobSkills)) {
                $jobSkills = json_decode($jobSkills, true) ?: explode(",", $jobSkills);
            }
            $wantedNormalized = array_map(fn($v) => $this->normalize((string)$v), $wantedSkills);
            $jobNormalized = array_map(fn($v) => $this->normalize((string)$v), is_array($jobSkills) ? $jobSkills : []);
            $value = $this->tokenOverlap($wantedNormalized, $jobNormalized);
            $add("skills", "Kỹ năng phù hợp", 15, $value);
        }

        $totalWeight = array_sum(array_column($criteria, "weight"));
        $earned = 0.0;
        foreach ($criteria as $criterion) {
            $earned += $criterion["weight"] * $criterion["value"];
        }
        $score = $totalWeight > 0 ? (int)round(($earned / $totalWeight) * 100) : 0;

        return ["score" => $score, "active_criteria" => count($criteria), "details" => $criteria];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim(strip_tags($value)), "UTF-8");
        $ascii = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value);
        $value = $ascii !== false ? strtolower($ascii) : $value;
        return trim((string)preg_replace('/[^a-z0-9]+/i', ' ', $value));
    }

    private function tokens(string $value): array
    {
        return array_values(array_unique(array_filter(explode(" ", $value), fn($token) => strlen($token) >= 2)));
    }

    private function tokenOverlap(array $wanted, array $actual): float
    {
        $wanted = array_values(array_unique(array_filter($wanted, fn($v) => $v !== "")));
        $actual = array_values(array_unique(array_filter($actual, fn($v) => $v !== "")));
        if ($wanted === []) {
            return 0.0;
        }
        return count(array_intersect($wanted, $actual)) / count($wanted);
    }
}
