<?php

declare(strict_types=1);

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->load();

use JobMarket\Domain\Assistant\GeminiClientInterface;
use JobMarket\Domain\Assistant\GeminiResponse;
use JobMarket\Domain\Cv\CvProfileExtractionService;
use JobMarket\Domain\Cv\CvStorageService;
use JobMarket\Domain\Matching\Services\PiiRedactor;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\ProfileRepository;
use JobMarket\Infrastructure\SkillRepository;

final class FakeCvGeminiClient implements GeminiClientInterface
{
    public bool $receivedPdf = false;

    public function isAvailable(): bool
    {
        return true;
    }

    public function generateContent(array $contents, ?string $systemInstruction = null, array $options = []): GeminiResponse
    {
        $inline = $contents[0]["parts"][1]["inlineData"] ?? [];
        $this->receivedPdf = ($inline["mimeType"] ?? "") === "application/pdf"
            && base64_decode((string)($inline["data"] ?? ""), true) !== false;

        return new GeminiResponse(json_encode([
            "university" => "Đại học Bách Khoa Hà Nội",
            "major" => "Công nghệ thông tin",
            "academic_year" => 3,
            "education" => ["degree" => "Kỹ sư", "grad_year" => "2027", "description" => "GPA 3.4/4"],
            "skills" => ["Barista", "Thu ngân", "Excel"],
            "work_experience" => [[
                "title" => "Nhân viên pha chế",
                "company" => "Quán cà phê sinh viên",
                "duration" => "6 tháng",
                "description" => "Pha chế và phục vụ khách hàng; email demo@example.com",
            ]],
            "certificates" => [["name" => "TOEIC 750", "year" => "2025"]],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}

function buildSyntheticCvPdf(): string
{
    $stream = "BT\n/F1 14 Tf\n50 790 Td\n(Student CV) Tj\n0 -24 Td\n(University: Hanoi University of Science and Technology) Tj\n0 -20 Td\n(Major: Information Technology - Year 3) Tj\n0 -20 Td\n(Skills: Barista, Cashier POS, Microsoft Excel, English communication) Tj\n0 -20 Td\n(Experience: Part-time barista at Student Coffee Shop for 6 months) Tj\n0 -20 Td\n(Certificate: TOEIC 750 - 2025) Tj\nET";
    $objects = [
        "<< /Type /Catalog /Pages 2 0 R >>",
        "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
        "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
        "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
    ];
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $number = $index + 1;
        $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    return $pdf;
}

$config = Config::env();
$db = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$userId = "user-student-01";
$select = $db->prepare(
    "SELECT cv_storage_path, cv_original_name, cv_mime_type, cv_file_size, cv_uploaded_at
     FROM student_profiles WHERE user_id = ?"
);
$select->execute([$userId]);
$backup = $select->fetch(PDO::FETCH_ASSOC);
if (!$backup) {
    throw new RuntimeException("Không tìm thấy hồ sơ sinh viên mẫu.");
}

$storage = new CvStorageService();
$temporary = tempnam(sys_get_temp_dir(), "synthetic-cv-");
file_put_contents($temporary, buildSyntheticCvPdf());
$storedName = $storage->store($temporary);
@unlink($temporary);

try {
    $update = $db->prepare(
        "UPDATE student_profiles
         SET cv_storage_path = ?, cv_original_name = ?, cv_mime_type = 'application/pdf', cv_file_size = ?, cv_uploaded_at = NOW()
         WHERE user_id = ?"
    );
    $update->execute([$storedName, "cv-sinh-vien-mau.pdf", filesize($storage->getAbsolutePath($storedName)), $userId]);

    $fake = new FakeCvGeminiClient();
    $service = new CvProfileExtractionService(
        $storage,
        new ProfileRepository(),
        $fake,
        new SkillRepository(),
        new PiiRedactor()
    );
    $result = $service->analyze(["id" => $userId, "role" => "student"]);

    $assertions = [
        "PDF được gửi dạng inlineData" => $fake->receivedPdf,
        "Gemini trả học vấn có cấu trúc" => $result["major"] === "Công nghệ thông tin",
        "Barista ánh xạ sang kỹ năng pha chế" => in_array("skill-001", $result["matched_skill_ids"], true),
        "Thu ngân được ánh xạ" => in_array("skill-002", $result["matched_skill_ids"], true),
        "Excel ánh xạ sang tin học văn phòng" => in_array("skill-006", $result["matched_skill_ids"], true),
        "Thông tin liên hệ bị loại bỏ" => !str_contains(json_encode($result), "demo@example.com"),
    ];
    foreach ($assertions as $label => $passed) {
        echo ($passed ? "[PASS] " : "[FAIL] ") . $label . PHP_EOL;
        if (!$passed) {
            throw new RuntimeException("Kiểm thử thất bại: {$label}");
        }
    }

    if (in_array("--live", $argv, true)) {
        $live = (new CvProfileExtractionService())->analyze(["id" => $userId, "role" => "student"]);
        echo "[PASS] Gemini thật đã đọc PDF: " . count($live["skills"]) . " kỹ năng, "
            . count($live["work_experience"]) . " kinh nghiệm" . PHP_EOL;
    }
} finally {
    $restore = $db->prepare(
        "UPDATE student_profiles
         SET cv_storage_path = ?, cv_original_name = ?, cv_mime_type = ?, cv_file_size = ?, cv_uploaded_at = ?
         WHERE user_id = ?"
    );
    $restore->execute([
        $backup["cv_storage_path"],
        $backup["cv_original_name"],
        $backup["cv_mime_type"],
        $backup["cv_file_size"],
        $backup["cv_uploaded_at"],
        $userId,
    ]);
    $storage->deleteFile($storedName);
}
