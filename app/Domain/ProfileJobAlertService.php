<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Matching\Adapters\CandidateProfileAdapter;
use JobMarket\Domain\Matching\Adapters\JobRequirementsAdapter;
use JobMarket\Domain\Matching\Services\DeterministicMatcher;
use JobMarket\Domain\Matching\Services\Normalizers\LocationNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\ScheduleNormalizer;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Infrastructure\CategoryRepository;
use JobMarket\Infrastructure\LocationRepository;
use JobMarket\Infrastructure\ProfileJobAlertRepository;
use JobMarket\Infrastructure\ProfileRepository;
use JobMarket\Infrastructure\SkillRepository;
use JobMarket\Support\Logger;

class ProfileJobAlertService
{
    public const MINIMUM_COVERAGE = 60;

    public function __construct(
        private ?ProfileJobAlertRepository $alerts = null,
        private ?ProfileRepository $profiles = null,
        private ?NotificationService $notifications = null,
        private ?MailService $mail = null
    ) {
        $this->alerts ??= new ProfileJobAlertRepository();
        $this->profiles ??= new ProfileRepository();
        $this->notifications ??= new NotificationService();
        $this->mail ??= new MailService();
    }

    public function getSettings(array $user): array
    {
        $this->authorizeStudent($user);
        if (!$this->profiles->findByUserId((string)$user["id"])) {
            throw new NotFoundException("Bạn cần tạo hồ sơ sinh viên trước khi bật thông báo cá nhân hóa.");
        }
        return $this->alerts->getSettings((string)$user["id"]);
    }

    public function updateSettings(array $user, array $data): array
    {
        $this->authorizeStudent($user);
        if (!$this->profiles->findByUserId((string)$user["id"])) {
            throw new NotFoundException("Bạn cần tạo hồ sơ sinh viên trước khi bật thông báo cá nhân hóa.");
        }
        $enabled = filter_var($data["enabled"] ?? false, FILTER_VALIDATE_BOOL);
        $emailEnabled = filter_var($data["email_enabled"] ?? false, FILTER_VALIDATE_BOOL);
        $minimumScore = max(50, min(90, (int)($data["minimum_score"] ?? 65)));

        return $this->alerts->updateSettings(
            (string)$user["id"],
            $enabled,
            $emailEnabled,
            $minimumScore
        );
    }

    /**
     * Matches one newly published job against opted-in student profiles.
     * Duplicate delivery is prevented by the unique (user_id, job_id) database key.
     */
    public function processPublishedJob(array $job): array
    {
        if (($job["status"] ?? "") !== "published" || empty($job["id"])) {
            return ["matched" => 0, "notified" => 0, "emailed" => 0];
        }

        $skillDictionary = $this->dictionary((new SkillRepository())->getAll());
        $locationDictionary = $this->dictionary((new LocationRepository())->getAll());
        $categoryDictionary = $this->dictionary((new CategoryRepository())->getAll());
        $redactor = new PiiRedactor();
        $candidateAdapter = new CandidateProfileAdapter(
            new SkillNormalizer($skillDictionary),
            new ScheduleNormalizer(),
            new LocationNormalizer($locationDictionary),
            $redactor
        );
        $jobAdapter = new JobRequirementsAdapter(
            new SkillNormalizer($skillDictionary),
            new ScheduleNormalizer(),
            new LocationNormalizer($locationDictionary),
            $categoryDictionary,
            $redactor
        );
        $matcher = new DeterministicMatcher();

        try {
            $jobContract = $jobAdapter->adapt($job);
        } catch (\Throwable $e) {
            Logger::warning("Không thể chuẩn hóa tin cho thông báo cá nhân hóa.", ["job_id" => $job["id"], "error" => $e->getMessage()]);
            return ["matched" => 0, "notified" => 0, "emailed" => 0];
        }

        $summary = ["matched" => 0, "notified" => 0, "emailed" => 0];
        foreach ($this->alerts->enabledProfilesForJob((string)$job["id"]) as $profile) {
            try {
                $result = $matcher->match($candidateAdapter->adapt($profile), $jobContract);
                $score = $result->overallScore === null ? 0 : (int)round($result->overallScore);
                $coverage = (int)round($result->coveragePercent);
                $threshold = max(50, min(90, (int)($profile["recommendation_minimum_score"] ?? 65)));
                if ($score < $threshold || $coverage < self::MINIMUM_COVERAGE) {
                    continue;
                }

                $details = [];
                foreach ($result->criteria as $criterion) {
                    if ($criterion->score === null) {
                        continue;
                    }
                    $details[] = [
                        "key" => $criterion->criterionName->value,
                        "label" => $this->criterionLabel($criterion->criterionName->value),
                        "score" => (int)round($criterion->score),
                        "matched" => $criterion->score >= 65,
                        "evidence" => $criterion->evidence,
                    ];
                }

                $summary["matched"]++;
                $alertId = $this->alerts->reserve($profile, (string)$job["id"], $score, $coverage, $details);
                if ($alertId === null) {
                    continue;
                }

                $notification = $this->notifications->notify(
                    (string)$profile["user_id"],
                    "Việc mới phù hợp với hồ sơ {$score}%",
                    "{$job['title']} tại " . ($job["company_name"] ?? "nhà tuyển dụng") . " phù hợp với kỹ năng và lịch rảnh của bạn.",
                    "profile_job_match",
                    [
                        "job_id" => $job["id"],
                        "match_score" => $score,
                        "coverage_percent" => $coverage,
                        "url" => "/viec-lam/" . rawurlencode((string)$job["id"]),
                    ]
                );
                $this->alerts->attachNotification($alertId, $notification->getId());
                $summary["notified"]++;

                if (!empty($profile["recommendation_email_enabled"])) {
                    $mailResult = $this->mail->sendProfileJobAlert(
                        ["name" => $profile["user_name"], "email" => $profile["user_email"]],
                        $job,
                        $score,
                        $coverage,
                        $details
                    );
                    $this->alerts->updateEmailResult($alertId, $mailResult);
                    if (in_array($mailResult["status"], ["sent", "preview"], true)) {
                        $summary["emailed"]++;
                    }
                }
            } catch (\Throwable $e) {
                Logger::error("Không thể xử lý thông báo hồ sơ cho sinh viên.", [
                    "job_id" => $job["id"],
                    "user_id" => $profile["user_id"] ?? null,
                    "error" => $e->getMessage(),
                ]);
            }
        }

        Logger::info("Đã xử lý thông báo việc làm theo hồ sơ.", ["job_id" => $job["id"]] + $summary);
        return $summary;
    }

    private function authorizeStudent(array $user): void
    {
        if (!in_array($user["role"] ?? "", ["student", "developer"], true)) {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có thể quản lý thông báo cá nhân hóa.");
        }
    }

    private function dictionary(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (isset($row["id"], $row["name"])) {
                $result[(string)$row["id"]] = (string)$row["name"];
            }
        }
        return $result;
    }

    private function criterionLabel(string $key): string
    {
        return [
            "age" => "Độ tuổi",
            "skills" => "Kỹ năng",
            "availability" => "Lịch rảnh và ca làm",
            "experience" => "Kinh nghiệm",
            "education" => "Học vấn",
        ][$key] ?? $key;
    }
}
