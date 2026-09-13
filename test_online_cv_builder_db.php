<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__, '.env.testing')->load();

use JobMarket\Domain\CvBuilder\OnlineCvService;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\OnlineCvRepository;

$config = Config::env();
$db = new PDO(
    "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4",
    $config['user'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$suffix = bin2hex(random_bytes(5));
$userId = 'test-cv-user-' . $suffix;
$email = "cv-{$suffix}@example.test";
$db->beginTransaction();

try {
    $stmt = $db->prepare(
        "INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, 'student', 'active')"
    );
    $stmt->execute([$userId, 'Sinh viên kiểm thử', $email, password_hash('test-only', PASSWORD_DEFAULT)]);

    $service = new OnlineCvService(new OnlineCvRepository($db));
    $user = ['id' => $userId, 'role' => 'student', 'name' => 'Sinh viên kiểm thử', 'email' => $email];
    $cv = $service->create($user, [
        'title' => 'CV kiểm thử database',
        'content' => ['personal' => ['phone' => '0901234567']],
        'is_public' => true,
    ]);

    if (($cv['version'] ?? null) !== 1 || empty($cv['public_url'])) {
        throw new RuntimeException('Không tạo hoặc hydrate CV đúng từ MySQL.');
    }

    $cv = $service->update($user, $cv['id'], [
        'expected_version' => 1,
        'style' => ['accent_color' => '#2563eb'],
        'hidden_sections' => ['interests'],
    ]);
    if (($cv['version'] ?? null) !== 2 || ($cv['style']['accent_color'] ?? null) !== '#2563eb') {
        throw new RuntimeException('Không update/version/hydrate JSON đúng từ MySQL.');
    }

    $public = $service->publicBySlug($cv['public_slug']);
    if (($public['id'] ?? null) !== $cv['id']) {
        throw new RuntimeException('Không đọc được CV công khai từ MySQL.');
    }

    $service->delete($user, $cv['id']);
    if ($service->list($user) !== []) {
        throw new RuntimeException('Xóa mềm không hoạt động đúng trên MySQL.');
    }

    echo "PASS: MySQL repository create, JSON hydrate, optimistic update, public read, soft-delete" . PHP_EOL;
} finally {
    $db->rollBack();
}
