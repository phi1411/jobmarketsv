<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();

use JobMarket\Domain\Job\Job;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\JobRepository;

function assertJobField(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("[FAIL] {$message}");
    }
    echo "[PASS] {$message}" . PHP_EOL;
}

$config = Config::env();
$db = new PDO(
    "mysql:dbname={$config['dbname']};host={$config['host']};charset=utf8mb4",
    $config['user'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$companyId = (string)$db->query("SELECT id FROM companies ORDER BY created_at ASC LIMIT 1")->fetchColumn();
assertJobField($companyId !== '', 'Có doanh nghiệp mẫu để kiểm tra lưu tin');

$jobId = 'job-five-criteria-test-' . bin2hex(random_bytes(5));
$repository = new JobRepository();

try {
    $job = Job::fromArray([
        'id' => $jobId,
        'company_id' => $companyId,
        'title' => 'Tin kiểm tra năm tiêu chí',
        'description' => 'Dữ liệu kiểm tra tự động cho yêu cầu tuyển dụng.',
        'status' => 'draft',
        'shift_type' => null,
        'required_skills' => ['skill-001', 'Chụp hình sản phẩm'],
        'minimum_age' => 18,
        'maximum_age' => 23,
    ]);
    $repository->create($job);

    $stored = $repository->findById($jobId);
    assertJobField((int)$stored['minimum_age'] === 18 && (int)$stored['maximum_age'] === 23, 'Lưu và đọc lại đúng khoảng tuổi');
    assertJobField($stored['shift_type'] === null, 'Cho phép bỏ trống yêu cầu ca làm');
    assertJobField(in_array('Chụp hình sản phẩm', $stored['skills'] ?? [], true), 'Lưu được kỹ năng tự nhập cùng kỹ năng tích chọn');

    $updated = Job::fromArray(array_merge($stored, ['minimum_age' => 19, 'maximum_age' => 24]));
    $repository->update($updated);
    $stored = $repository->findById($jobId);
    assertJobField((int)$stored['minimum_age'] === 19 && (int)$stored['maximum_age'] === 24, 'Cập nhật được khoảng tuổi khi chỉnh sửa tin');
} finally {
    $stmt = $db->prepare('DELETE FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
}

assertJobField(!$repository->findById($jobId), 'Dữ liệu kiểm tra tạm đã được dọn sạch');
echo 'Hoàn tất kiểm tra trường tuyển dụng cho matcher.' . PHP_EOL;
