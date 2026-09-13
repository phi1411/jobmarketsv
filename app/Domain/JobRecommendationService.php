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
                if ($criterion->weight <= 0) {
                    continue;
                }

                $score = $criterion->score === null ? null : (int)round($criterion->score);
                $details = is_array($criterion->details) ? $criterion->details : [];
                $criteria[] = [
                    "key" => $criterion->criterionName->value,
                    "label" => $this->criterionLabel($criterion->criterionName->value),
                    "score" => $score,
                    "weight" => (int)round($criterion->weight),
                    "state" => $criterion->state->value,
                    "status" => $this->criterionStatus($score, $criterion->state->value),
                    "evidence" => $criterion->evidence,
                    "matched_items" => $this->detailStrings($details["matched"] ?? []),
                    "missing_items" => $this->detailStrings($details["unmatched"] ?? []),
                ];
            }

            $criteriaByScore = $criteria;
            usort($criteriaByScore, function (array $a, array $b): int {
                return ($b["score"] ?? -1) <=> ($a["score"] ?? -1);
            });
            $reasons = array_slice($result->strengths, 0, 2);
            if ($reasons === []) {
                foreach ($criteriaByScore as $criterion) {
                    if ($criterion["score"] !== null && $criterion["score"] >= 65) {
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
                "considerations" => array_slice($result->considerations, 0, 3),
                "criteria" => $criteria,
                "improvement_suggestions" => $this->improvementSuggestions($criteria),
                "score_disclaimer" => $result->disclaimer,
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
            "age" => "Độ tuổi",
            "skills" => "Kỹ năng",
            "availability" => "Lịch rảnh và ca làm",
            "experience" => "Kinh nghiệm",
            "education" => "Học vấn",
        ][$key] ?? $key;
    }

    private function criterionStatus(?int $score, string $state): string
    {
        if ($state === "NOT_APPLICABLE") {
            return "not_applicable";
        }
        if ($state !== "AVAILABLE" || $score === null) {
            return "missing_data";
        }
        if ($score >= 70) {
            return "matched";
        }
        if ($score >= 40) {
            return "partial";
        }
        return "needs_improvement";
    }

    /** @return list<string> */
    private function detailStrings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach (array_slice($value, 0, 8) as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $text = trim((string)$item);
            if ($text !== "") {
                $items[] = mb_substr($text, 0, 120);
            }
        }
        return array_values(array_unique($items));
    }

    /**
     * @param list<array<string, mixed>> $criteria
     * @return list<array{key:string,label:string,text:string}>
     */
    private function improvementSuggestions(array $criteria): array
    {
        $candidates = [];
        foreach ($criteria as $criterion) {
            if (in_array($criterion["status"] ?? "", ["matched", "not_applicable"], true)) {
                continue;
            }

            $text = $this->suggestionForCriterion($criterion);
            if ($text === null) {
                continue;
            }

            $score = is_numeric($criterion["score"] ?? null) ? (int)$criterion["score"] : 0;
            $weight = (int)($criterion["weight"] ?? 0);
            $candidates[] = [
                "key" => (string)$criterion["key"],
                "label" => (string)$criterion["label"],
                "text" => $text,
                "impact" => $weight * (100 - $score),
            ];
        }

        usort($candidates, fn(array $a, array $b): int => $b["impact"] <=> $a["impact"]);
        return array_map(
            fn(array $item): array => ["key" => $item["key"], "label" => $item["label"], "text" => $item["text"]],
            array_slice($candidates, 0, 3)
        );
    }

    /** @param array<string, mixed> $criterion */
    private function suggestionForCriterion(array $criterion): ?string
    {
        $key = (string)($criterion["key"] ?? "");
        $missing = $criterion["missing_items"] ?? [];
        $hasData = ($criterion["state"] ?? "") === "AVAILABLE";

        return match ($key) {
            "age" => $hasData
                ? "Độ tuổi nằm ngoài khoảng yêu cầu; hãy ưu tiên các tin không giới hạn tuổi hoặc có khoảng tuổi phù hợp."
                : "Bổ sung ngày sinh trong hồ sơ để hệ thống đối chiếu yêu cầu tuổi.",
            "skills" => $missing !== []
                ? "Nếu bạn đã có " . implode(", ", array_slice($missing, 0, 3)) . ", hãy bổ sung vào hồ sơ; nếu chưa, đây là các kỹ năng nên ưu tiên học."
                : "Cập nhật đầy đủ các kỹ năng thực tế liên quan đến công việc trong hồ sơ.",
            "availability" => $hasData
                ? "Kiểm tra lại ca làm của tin và cập nhật lịch rảnh chính xác; bạn cũng có thể trao đổi với nhà tuyển dụng về việc đổi ca."
                : "Thêm lịch rảnh theo từng ngày và buổi để hệ thống đối chiếu chính xác với ca làm.",
            "experience" => $hasData
                ? "Mô tả rõ thời gian và nhiệm vụ ở các công việc hoặc dự án có liên quan đến vị trí này."
                : "Bổ sung kinh nghiệm làm thêm, hoạt động câu lạc bộ hoặc dự án có liên quan nếu bạn thực sự đã tham gia.",
            "education" => $hasData
                ? "Kiểm tra yêu cầu học vấn của tin và làm rõ ngành học, năm học hoặc chứng chỉ liên quan trong hồ sơ."
                : "Bổ sung trường, ngành, năm học và các chứng chỉ liên quan để hoàn thiện phần học vấn.",
            default => null,
        };
    }
}
