<?php

declare(strict_types=1);

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
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\CategoryRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Infrastructure\LocationRepository;
use JobMarket\Infrastructure\ProfileRepository;
use JobMarket\Infrastructure\SkillRepository;
use PDO;

class JobRecommendationService
{
    private ProfileRepository $profiles;
    private JobRepository $jobs;
    private PDO $db;

    public function __construct(?ProfileRepository $profiles = null, ?JobRepository $jobs = null)
    {
        $this->profiles = $profiles ?? new ProfileRepository();
        $this->jobs = $jobs ?? new JobRepository();

        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function recommend(array $user, int $limit = 12, int $minimumScore = 0): array
    {
        if (!in_array($user["role"] ?? "", ["student", "developer"], true)) {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có thể xem việc làm được gợi ý.");
        }

        $profile = $this->profiles->findByUserId((string)($user["id"] ?? ""));
        if (!$profile) {
            throw new NotFoundException("Bạn cần tạo hồ sơ sinh viên trước khi nhận gợi ý việc làm.");
        }

        $limit = max(1, min(50, $limit));
        $minimumScore = max(0, min(100, $minimumScore));

        $skillDictionary = $this->dictionary((new SkillRepository())->getAll());
        $locationDictionary = $this->dictionary((new LocationRepository())->getAll());
        $categoryDictionary = $this->dictionary((new CategoryRepository())->getAll());

        $skillNormalizer = new SkillNormalizer($skillDictionary);
        $scheduleNormalizer = new ScheduleNormalizer();
        $locationNormalizer = new LocationNormalizer($locationDictionary);
        $redactor = new PiiRedactor();
        $candidateAdapter = new CandidateProfileAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, $redactor);
        $jobAdapter = new JobRequirementsAdapter($skillNormalizer, $scheduleNormalizer, $locationNormalizer, $categoryDictionary, $redactor);
        $matcher = new DeterministicMatcher();
        $candidate = $candidateAdapter->adapt($profile);

        $applied = $this->idSet("SELECT job_id FROM applications WHERE developer_id = ?", (string)$user["id"]);
        $favorite = $this->idSet("SELECT job_id FROM favorites WHERE user_id = ?", (string)$user["id"]);
        $ranked = [];

        foreach ($this->jobs->search(["is_public" => true]) as $job) {
            if (isset($applied[(string)$job["id"]])) {
                continue;
            }

            try {
                $result = $matcher->match($candidate, $jobAdapter->adapt($job));
            } catch (\Throwable) {
                continue;
            }

            if ($result->overallScore === null || $result->overallScore < $minimumScore) {
                continue;
            }

            $criteria = [];
            foreach ($result->criteria as $criterion) {
                if ($criterion->score === null) {
                    continue;
                }
                $criteria[] = [
                    "key" => $criterion->criterionName->value,
                    "label" => $this->criterionLabel($criterion->criterionName->value),
                    "score" => $criterion->score,
                    "evidence" => $criterion->evidence,
                ];
            }

            usort($criteria, fn(array $a, array $b): int => $b["score"] <=> $a["score"]);
            $reasons = array_slice($result->strengths, 0, 2);
            if ($reasons === []) {
                foreach ($criteria as $criterion) {
                    if ($criterion["score"] >= 65) {
                        $reasons[] = $criterion["evidence"] ?: $criterion["label"] . " phù hợp";
                    }
                    if (count($reasons) >= 2) {
                        break;
                    }
                }
            }

            $ranked[] = [
                "id" => $job["id"],
                "title" => $job["title"],
                "company_name" => $job["company_name"] ?? "Nhà tuyển dụng",
                "company_logo" => $job["company_logo"] ?? null,
                "category_name" => $job["category_name"] ?? null,
                "location" => $job["location"] ?? ($job["city"] ?? null),
                "city" => $job["city"] ?? null,
                "district" => $job["district"] ?? null,
                "shift_type" => $job["shift_type"] ?? null,
                "work_mode" => $job["work_mode"] ?? null,
                "salary_type" => $job["salary_type"] ?? null,
                "salary_min" => isset($job["salary_min"]) ? (int)$job["salary_min"] : null,
                "salary_max" => isset($job["salary_max"]) ? (int)$job["salary_max"] : null,
                "currency" => $job["currency"] ?? "VND",
                "application_deadline" => $job["application_deadline"] ?? null,
                "published_at" => $job["published_at"] ?? ($job["created_at"] ?? null),
                "match_score" => (int)round($result->overallScore),
                "coverage_percent" => (int)round($result->coveragePercent),
                "classification" => $result->classification->value,
                "reasons" => $reasons,
                "consideration" => $result->considerations[0] ?? null,
                "criteria" => $criteria,
                "is_favorite" => isset($favorite[(string)$job["id"]]),
            ];
        }

        usort($ranked, function (array $a, array $b): int {
            return [$b["match_score"], $b["coverage_percent"], (string)$b["published_at"]]
                <=> [$a["match_score"], $a["coverage_percent"], (string)$a["published_at"]];
        });

        return [
            "items" => array_slice($ranked, 0, $limit),
            "total" => count($ranked),
            "profile" => [
                "completion_percent" => (int)($profile["profile_completion_percent"] ?? 0),
                "has_skills" => !empty($profile["skill_ids"]) || !empty($profile["skills"]),
                "has_schedule" => !empty($profile["available_schedule"]),
                "has_location" => !empty($profile["location_id"]) || !empty($profile["preferred_location"]) || !empty($profile["preferred_locations"]),
                "needs_update" => empty($profile["skills"]) || empty($profile["available_schedule"]),
            ],
            "excluded_applied" => count($applied),
            "matcher_version" => DeterministicMatcher::MATCHER_VERSION,
        ];
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

    private function idSet(string $sql, string $userId): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return array_fill_keys(array_map("strval", $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
    }

    private function criterionLabel(string $key): string
    {
        return [
            "skills" => "Kỹ năng",
            "availability" => "Lịch rảnh và ca làm",
            "experience" => "Kinh nghiệm",
            "education" => "Học vấn",
            "location" => "Khu vực",
            "role_relevance" => "Vị trí mong muốn",
            "salary" => "Mức lương",
        ][$key] ?? $key;
    }
}
