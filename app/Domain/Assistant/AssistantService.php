<?php

namespace JobMarket\Domain\Assistant;

use JobMarket\Domain\Assistant\Exceptions\GeminiException;
use JobMarket\Domain\Assistant\Exceptions\GeminiUnavailableException;
use JobMarket\Domain\Job\JobRepositoryInterface;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Infrastructure\Gemini\GeminiClient;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Support\Pagination;

class AssistantService
{
    public const MAX_MESSAGE_LENGTH = 1000;
    public const MAX_HISTORY_TURNS = 10;
    public const MAX_HISTORY_TOTAL_LENGTH = 4000;
    public const MAX_CONTEXT_JOBS = 5;

    private GeminiClientInterface $client;
    private JobRepositoryInterface $jobRepo;

    public function __construct(
        ?GeminiClientInterface $client = null,
        ?JobRepositoryInterface $jobRepo = null
    ) {
        $this->client = $client ?? new GeminiClient();
        $this->jobRepo = $jobRepo ?? new JobRepository();
    }

    /**
     * Handle a chat request safely.
     *
     * @param mixed $rawMessage User input message
     * @param mixed $rawHistory Conversation history turns
     * @return array [ 'answer' => string, 'job_links' => array ]
     * @throws ValidationException
     * @throws GeminiException
     */
    public function handleChat(mixed $rawMessage, mixed $rawHistory = null): array
    {
        // 1. Validate user message
        $message = $this->validateMessage($rawMessage);

        // 2. Validate history turns
        $history = $this->validateHistory($rawHistory);

        // 3. Fail closed if Gemini is unavailable
        if (!$this->client->isAvailable()) {
            throw new GeminiUnavailableException("Tính năng trợ lý AI hiện đang tạm tắt hoặc chưa sẵn sàng.");
        }

        // 4. Retrieve public published jobs for safe server context
        $vettedJobs = $this->getPublicJobContext($message);

        // 5. Construct server-owned Vietnamese system instruction
        $systemInstruction = $this->buildSystemInstruction($vettedJobs);

        // 6. Format contents for Gemini
        $contents = $this->formatContents($history, $message);

        // 7. Invoke Gemini
        $response = $this->client->generateContent($contents, $systemInstruction);

        // 8. If content was safety-blocked, return safe fallback
        if ($response->isBlocked) {
            return [
                "answer"    => $response->text,
                "job_links" => []
            ];
        }

        // 9. Extract and verify safe server-owned job links
        $jobLinks = $this->resolveSafeJobLinks($response->text, $vettedJobs);

        return [
            "answer"    => $response->text,
            "job_links" => $jobLinks
        ];
    }

    private function validateMessage(mixed $message): string
    {
        if (!is_string($message)) {
            throw new ValidationException(["message" => ["Nội dung tin nhắn phải là chuỗi văn bản."]]);
        }

        $trimmed = trim($message);
        if ($trimmed === "") {
            throw new ValidationException(["message" => ["Vui lòng nhập nội dung tin nhắn."]]);
        }

        if (mb_strlen($trimmed, "UTF-8") > self::MAX_MESSAGE_LENGTH) {
            throw new ValidationException([
                "message" => ["Nội dung tin nhắn quá dài. Vui lòng nhập tối đa " . self::MAX_MESSAGE_LENGTH . " ký tự."]
            ]);
        }

        return $trimmed;
    }

    private function validateHistory(mixed $history): array
    {
        if ($history === null || $history === "") {
            return [];
        }

        if (!is_array($history)) {
            throw new ValidationException(["history" => ["Lịch sử hội thoại phải là danh sách các lượt tin nhắn."]]);
        }

        if (count($history) > self::MAX_HISTORY_TURNS) {
            throw new ValidationException([
                "history" => ["Lịch sử hội thoại vượt quá giới hạn tối đa " . self::MAX_HISTORY_TURNS . " lượt."]
            ]);
        }

        $totalLength = 0;
        $validated = [];

        foreach ($history as $index => $turn) {
            if (!is_array($turn)) {
                throw new ValidationException([
                    "history" => ["Lượt hội thoại thứ " . ($index + 1) . " không đúng định dạng."]
                ]);
            }

            $role = $turn["role"] ?? null;
            if ($role !== "user" && $role !== "model") {
                throw new ValidationException([
                    "history" => ["Vai trò ở lượt hội thoại " . ($index + 1) . " chỉ được là 'user' hoặc 'model'."]
                ]);
            }

            // Extract text from 'text' or 'parts'
            $text = "";
            if (isset($turn["text"]) && is_string($turn["text"])) {
                $text = trim($turn["text"]);
            } elseif (isset($turn["parts"]) && is_array($turn["parts"])) {
                foreach ($turn["parts"] as $part) {
                    if (is_array($part) && isset($part["text"]) && is_string($part["text"])) {
                        $text .= " " . trim($part["text"]);
                    }
                }
                $text = trim($text);
            }

            if ($text === "") {
                throw new ValidationException([
                    "history" => ["Nội dung tin nhắn ở lượt " . ($index + 1) . " không được để trống."]
                ]);
            }

            $totalLength += mb_strlen($text, "UTF-8");
            if ($totalLength > self::MAX_HISTORY_TOTAL_LENGTH) {
                throw new ValidationException([
                    "history" => ["Tổng độ dài lịch sử chat vượt quá giới hạn " . self::MAX_HISTORY_TOTAL_LENGTH . " ký tự."]
                ]);
            }

            $validated[] = [
                "role"  => $role,
                "parts" => [
                    ["text" => $text]
                ]
            ];
        }

        return $validated;
    }

    private function getPublicJobContext(string $message): array
    {
        try {
            // Attempt to search relevant public jobs based on keywords in message
            $keywords = $this->extractKeywords($message);
            $filters = [
                "is_public" => true,
                "sort_by"   => "newest"
            ];

            if (!empty($keywords)) {
                $filters["keyword"] = $keywords;
            }

            $pagination = new Pagination(page: 1, perPage: self::MAX_CONTEXT_JOBS);
            $rawJobs = $this->jobRepo->search($filters, $pagination);

            // Fallback to recent public jobs if keyword matched nothing
            if (empty($rawJobs) && !empty($keywords)) {
                unset($filters["keyword"]);
                $rawJobs = $this->jobRepo->search($filters, $pagination);
            }

            return $this->vetAndFormatJobs($rawJobs);
        } catch (\Throwable) {
            // Failure to load job context should not crash the assistant; fail safely with empty job list
            return [];
        }
    }

    /**
     * Vets and formats raw job records according to public privacy and verification policies.
     *
     * @param array $rawJobs
     * @return array
     */
    public function vetAndFormatJobs(array $rawJobs): array
    {
        $allowlisted = [];
        $today = strtotime(date("Y-m-d"));

        foreach ($rawJobs as $job) {
            // 1. Strictly enforce published status (reject draft, closed, pending, etc.)
            if (($job["status"] ?? "") !== "published") {
                continue;
            }

            // 2. Reject deleted jobs
            if (!empty($job["deleted_at"])) {
                continue;
            }

            // 3. Reject inactive / hidden jobs
            if (isset($job["is_active"])) {
                if ($job["is_active"] === 0 || $job["is_active"] === false || $job["is_active"] === "0") {
                    continue;
                }
            }

            // 4. Reject expired jobs (check application_deadline and deadline)
            $deadline = $job["application_deadline"] ?? $job["deadline"] ?? null;
            if (!empty($deadline) && strtotime($deadline) < $today) {
                continue;
            }

            // 5. Valid job ID is required
            $jobId = (string)($job["id"] ?? "");
            if (empty($jobId)) {
                continue;
            }

            // 6. Enforce verified-company policy:
            // Only include company name if verification_status is explicitly 'verified'.
            // For pending, rejected, unknown, or unverified company records, use the defined generic label.
            $vStatus = strtolower((string)($job["verification_status"] ?? ""));
            if ($vStatus === "verified" && !empty($job["company_name"])) {
                $companyName = (string)$job["company_name"];
            } else {
                $companyName = "Doanh nghiệp tuyển dụng";
            }

            // 7. Format salary safely
            $salaryMin = $job["salary_min"] ?? null;
            $salaryMax = $job["salary_max"] ?? null;
            $salaryStr = "Thỏa thuận";
            if ($salaryMin !== null && $salaryMax !== null) {
                $salaryStr = number_format((float)$salaryMin, 0, ',', '.') . "đ - " . number_format((float)$salaryMax, 0, ',', '.') . "đ/giờ";
            } elseif ($salaryMin !== null) {
                $salaryStr = "Từ " . number_format((float)$salaryMin, 0, ',', '.') . "đ/giờ";
            }

            // 8. Public allowlist: NEVER include contact_person, contact_phone, employer email, or notes
            $allowlisted[] = [
                "id"           => $jobId,
                "title"        => (string)($job["title"] ?? "Việc làm part-time"),
                "company_name" => $companyName,
                "location"     => (string)($job["district"] ?? $job["city"] ?? "TP. Hồ Chí Minh"),
                "shift"        => (string)($job["shift_type"] ?? $job["shift_information"] ?? "Linh hoạt theo ca"),
                "salary"       => $salaryStr,
                "deadline"     => $deadline ? date("d/m/Y", strtotime($deadline)) : "Đang mở",
                "url"          => "/viec-lam/" . $jobId
            ];

            if (count($allowlisted) >= self::MAX_CONTEXT_JOBS) {
                break;
            }
        }

        return $allowlisted;
    }

    private function extractKeywords(string $message): ?string
    {
        $messageLower = mb_strtolower($message, "UTF-8");
        $commonTerms = [
            "phục vụ", "pha chế", "barista", "thu ngân", "bán hàng",
            "gia sư", "tạp vụ", "bảo vệ", "giao hàng", "shipper",
            "lễ tân", "kho", "nhập liệu", "marketing", "editor",
            "ca sáng", "ca tối", "ca đêm", "linh hoạt", "part-time"
        ];

        $matched = [];
        foreach ($commonTerms as $term) {
            if (str_contains($messageLower, $term)) {
                $matched[] = $term;
            }
        }

        return !empty($matched) ? implode(" ", array_slice($matched, 0, 2)) : null;
    }

    private function buildSystemInstruction(array $vettedJobs): string
    {
        $instruction = "Bạn là trợ lý ảo JobMarketSV, chuyên hỗ trợ sinh viên và người tìm việc giải đáp thắc mắc về việc làm bán thời gian (part-time) tại Việt Nam.\n";
        $instruction .= "Quy tắc bắt buộc:\n";
        $instruction .= "1. Trả lời bằng tiếng Việt lịch sự, thân thiện, rõ ràng, ngắn gọn và thực tế.\n";
        $instruction .= "2. Chỉ hỗ trợ tư vấn hướng dẫn sử dụng nền tảng JobMarketSV, quy trình chuẩn bị hồ sơ/CV, cách nộp đơn và thông tin các tin tuyển dụng công khai được cung cấp trong danh sách bên dưới.\n";
        $instruction .= "3. Không đưa ra lời hứa hẹn về việc chắc chắn được tuyển dụng, không đánh giá hồ sơ/con người, không tư vấn pháp lý, tài chính hoặc y tế.\n";
        $instruction .= "4. Tuyệt đối không yêu cầu người dùng cung cấp mật khẩu, token, thông tin thẻ ngân hàng, CCCD hoặc dữ liệu cá nhân nhạy cảm.\n";
        $instruction .= "5. Bạn là trợ lý chỉ đọc (read-only): KHÔNG thể tự ý nộp đơn ứng tuyển, lưu việc làm, thay đổi trạng thái hồ sơ hay sửa thông tin. Hãy hướng dẫn người dùng tự thực hiện các thao tác này trên giao diện web.\n";
        $instruction .= "6. Tuyệt đối không bịa đặt tin tuyển dụng hoặc tự chế link URL. Chỉ được nhắc đến các tin việc làm có trong danh mục dưới đây kèm ID chính xác. Nếu không có việc phù hợp trong danh mục, hãy khuyên người dùng vào trang /viec-lam để dùng bộ lọc chi tiết.\n\n";

        if (!empty($vettedJobs)) {
            $instruction .= "DANH SÁCH VIỆC LÀM CÔNG KHAI ĐANG MỞ TRÊN HỆ THỐNG:\n";
            foreach ($vettedJobs as $j) {
                $instruction .= "- [Mã: {$j['id']}] {$j['title']} | Công ty: {$j['company_name']} | Địa điểm: {$j['location']} | Ca: {$j['shift']} | Lương: {$j['salary']} | Hạn nộp: {$j['deadline']}\n";
            }
        } else {
            $instruction .= "Hiện chưa có tin tuyển dụng nào được nạp vào ngữ cảnh. Hãy hướng dẫn người dùng truy cập trang /viec-lam để tìm kiếm.\n";
        }

        return $instruction;
    }

    private function formatContents(array $history, string $currentMessage): array
    {
        $contents = $history;
        $contents[] = [
            "role"  => "user",
            "parts" => [
                ["text" => $currentMessage]
            ]
        ];

        return $contents;
    }

    private function resolveSafeJobLinks(string $answerText, array $vettedJobs): array
    {
        if (empty($vettedJobs)) {
            return [];
        }

        $matchedLinks = [];
        $answerLower = mb_strtolower($answerText, "UTF-8");

        foreach ($vettedJobs as $job) {
            $id = $job["id"];
            $titleLower = mb_strtolower($job["title"], "UTF-8");

            // Check if Gemini referenced the job ID or job title in its answer
            $idMentioned = str_contains($answerText, $id) || str_contains($answerLower, mb_strtolower($id, "UTF-8"));
            $titleMentioned = str_contains($answerLower, $titleLower);

            if ($idMentioned || $titleMentioned) {
                $matchedLinks[] = [
                    "id"           => $job["id"],
                    "title"        => $job["title"],
                    "company_name" => $job["company_name"],
                    "salary"       => $job["salary"],
                    "url"          => "/viec-lam/" . rawurlencode($job["id"])
                ];
            }
        }

        return $matchedLinks;
    }
}
