<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__, '.env.testing')->load();

use JobMarket\Domain\Cv\CvStorageService;
use JobMarket\Domain\Cv\StudentCvService;
use JobMarket\Domain\CvBuilder\OnlineCvService;
use JobMarket\Facades\Config;
use JobMarket\Http\Controllers\OnlineCvController;
use JobMarket\Http\Request;
use JobMarket\Infrastructure\OnlineCvRepository;

$config = Config::env();
$db = new PDO(
    "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4",
    $config['user'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$suffix = bin2hex(random_bytes(5));
$userId = 'test-cv-activate-' . $suffix;
$email = "cv-activate-{$suffix}@example.test";
$user = ['id' => $userId, 'role' => 'student', 'name' => 'Sinh viên kích hoạt', 'email' => $email];
$cvId = null;

try {
    $stmt = $db->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, 'student', 'active')");
    $stmt->execute([$userId, $user['name'], $email, password_hash('test-only', PASSWORD_DEFAULT)]);

    $service = new OnlineCvService(new OnlineCvRepository($db));
    $cv = $service->create($user, [
        'title' => 'CV dùng để ứng tuyển',
        'content' => [
            'personal' => ['phone' => '0901234567', 'job_title' => 'Backend Intern'],
            'education' => [[
                'school' => 'Đại học Kiểm thử', 'degree' => 'Cử nhân', 'major' => 'CNTT',
                'start_date' => '2023-09', 'end_date' => '2027-06', 'gpa' => '', 'description' => '',
            ]],
        ],
    ]);
    $cvId = $cv['id'];

    $request = new Request([], ['expected_version' => 1], [], [], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => "/student/cvs/{$cvId}/activate",
        'CONTENT_TYPE' => 'application/json',
    ]);
    $request->setUser($user);
    $response = (new OnlineCvController($service))->activate($request, $cvId);
    $payload = $response->getPayload();
    if (empty($payload['success']) || empty($payload['data']['active_cv']['file_name'])) {
        throw new RuntimeException('Endpoint activate không trả metadata active CV.');
    }

    $stmt = $db->prepare("SELECT cv_storage_path, cv_mime_type FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$profile || $profile['cv_mime_type'] !== 'application/pdf' || !(new CvStorageService())->fileExists($profile['cv_storage_path'])) {
        throw new RuntimeException('PDF chưa được lưu vào private storage/profile.');
    }

    echo "PASS: online CV rendered and activated as private application PDF" . PHP_EOL;
} finally {
    try {
        (new StudentCvService())->deleteActiveCv($user);
    } catch (Throwable) {
    }
    if ($cvId !== null) {
        $stmt = $db->prepare("DELETE FROM online_cvs WHERE id = ?");
        $stmt->execute([$cvId]);
    }
    $stmt = $db->prepare("DELETE FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
}
