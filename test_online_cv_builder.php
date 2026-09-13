<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use JobMarket\Domain\CvBuilder\CvHtmlRenderer;
use JobMarket\Domain\CvBuilder\OnlineCvRepositoryInterface;
use JobMarket\Domain\CvBuilder\OnlineCvService;
use JobMarket\Exceptions\AppException;

final class InMemoryOnlineCvRepository implements OnlineCvRepositoryInterface
{
    public array $rows = [];

    public function listByUser(string $userId): array
    {
        $rows = array_values(array_filter($this->rows, fn(array $row) => $row['user_id'] === $userId && empty($row['deleted_at'])));
        usort($rows, fn(array $a, array $b) => ((int)$b['is_primary'] <=> (int)$a['is_primary']) ?: strcmp($b['updated_at'], $a['updated_at']));
        return $rows;
    }

    public function findOwned(string $id, string $userId): ?array
    {
        $row = $this->rows[$id] ?? null;
        return $row && $row['user_id'] === $userId && empty($row['deleted_at']) ? $row : null;
    }

    public function findPublicBySlug(string $slug): ?array
    {
        foreach ($this->rows as $row) {
            if ($row['public_slug'] === $slug && !empty($row['is_public']) && empty($row['deleted_at'])) {
                return $row;
            }
        }
        return null;
    }

    public function countByUser(string $userId): int
    {
        return count($this->listByUser($userId));
    }

    public function create(array $cv): void
    {
        $cv['created_at'] = '2026-09-13 12:00:00';
        $cv['updated_at'] = '2026-09-13 12:00:00';
        $cv['deleted_at'] = null;
        $cv['last_exported_at'] = null;
        $this->rows[$cv['id']] = $cv;
    }

    public function update(string $id, string $userId, array $changes, ?int $expectedVersion = null): bool
    {
        $row = $this->findOwned($id, $userId);
        if (!$row || ($expectedVersion !== null && $row['version'] !== $expectedVersion)) {
            return false;
        }
        $map = [
            'content_json' => 'content', 'style_json' => 'style',
            'section_order_json' => 'section_order', 'hidden_sections_json' => 'hidden_sections',
        ];
        foreach ($changes as $key => $value) {
            $row[$map[$key] ?? $key] = $value;
        }
        if ($changes !== []) {
            $row['version']++;
        }
        $row['updated_at'] = '2026-09-13 12:01:00';
        $this->rows[$id] = $row;
        return true;
    }

    public function softDelete(string $id, string $userId): bool
    {
        $row = $this->findOwned($id, $userId);
        if (!$row) {
            return false;
        }
        $row['deleted_at'] = '2026-09-13 12:02:00';
        $row['is_public'] = false;
        $row['is_primary'] = false;
        $this->rows[$id] = $row;
        return true;
    }

    public function clearPrimary(string $userId, ?string $exceptId = null): void
    {
        foreach ($this->rows as $id => $row) {
            if ($row['user_id'] === $userId && $id !== $exceptId && empty($row['deleted_at'])) {
                if (!empty($this->rows[$id]['is_primary'])) {
                    $this->rows[$id]['is_primary'] = false;
                    $this->rows[$id]['version']++;
                }
            }
        }
    }

    public function touchExported(string $id): void
    {
        if (isset($this->rows[$id])) {
            $this->rows[$id]['last_exported_at'] = '2026-09-13 12:03:00';
        }
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$repo = new InMemoryOnlineCvRepository();
$service = new OnlineCvService($repo);
$user = ['id' => 'student-test', 'role' => 'student', 'name' => 'Nguyễn An', 'email' => 'an@example.test'];

$cv = $service->create($user, ['title' => 'CV Thực tập PHP']);
assertTrue($cv['is_primary'] === true, 'CV đầu tiên phải tự động là CV chính.');
assertTrue($cv['version'] === 1, 'CV mới phải có version 1.');
assertTrue($cv['public_url'] === null, 'CV mới phải riêng tư mặc định.');

$cv = $service->update($user, $cv['id'], [
    'expected_version' => 1,
    'content' => [
        'personal' => ['job_title' => 'PHP Intern', 'phone' => '0901234567'],
        'summary' => 'Sinh viên chủ động, mong muốn phát triển backend.',
        'education' => [[
            'school' => 'Đại học Mở', 'degree' => 'Cử nhân', 'major' => 'Công nghệ thông tin',
            'start_date' => '2023-09', 'end_date' => '2027-06', 'gpa' => '3.4/4',
            'description' => 'Đồ án về hệ thống tuyển dụng.',
        ]],
        'skills' => [['name' => 'PHP', 'level' => 'Khá']],
        'projects' => [[
            'name' => 'Job Marketplace', 'role' => 'Backend Developer', 'url' => 'https://example.test/project',
            'start_date' => '2026-01', 'end_date' => '2026-08', 'description' => "- Xây API\n- Viết kiểm thử",
            'technologies' => 'PHP, MySQL',
        ]],
    ],
    'is_public' => true,
]);
assertTrue($cv['version'] === 2, 'Lưu CV phải tăng version.');
assertTrue($cv['completion_percent'] >= 75, 'Điểm hoàn thiện phải phản ánh nội dung đã nhập.');
assertTrue(is_string($cv['public_url']) && str_contains($cv['public_url'], '/cv/'), 'CV công khai phải có link chia sẻ.');

$public = $service->publicBySlug($cv['public_slug']);
assertTrue(!isset($public['user_id']) && !isset($public['public_slug']), 'API công khai không được lộ owner id hay slug nội bộ.');

$conflicted = false;
try {
    $service->update($user, $cv['id'], ['expected_version' => 1, 'title' => 'Bản cũ']);
} catch (AppException $e) {
    $conflicted = $e->getStatusCode() === 409;
}
assertTrue($conflicted, 'Phải chặn bản autosave cũ bằng HTTP 409.');

$copy = $service->duplicate($user, $cv['id']);
assertTrue($copy['is_public'] === false && $copy['is_primary'] === false, 'CV nhân bản phải riêng tư và không tự thành CV chính.');
$copy = $service->update($user, $copy['id'], ['expected_version' => 1, 'is_primary' => true]);
assertTrue($copy['is_primary'] === true, 'Phải đặt được CV nhân bản làm CV chính.');
assertTrue($service->get($user, $cv['id'])['is_primary'] === false, 'Chỉ được có một CV chính.');

$repo->rows[$copy['id']]['content']['summary'] = '<script>alert(1)</script>';
$html = (new CvHtmlRenderer())->render($repo->rows[$copy['id']], true);
assertTrue(!str_contains($html, '<script>alert(1)</script>'), 'Renderer phải escape nội dung do người dùng nhập.');
assertTrue(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Renderer phải giữ nội dung dưới dạng text an toàn.');

$dompdf = new Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4');
$dompdf->render();
$pdf = $dompdf->output();
assertTrue(str_starts_with($pdf, '%PDF-'), 'Kết quả xuất phải là tệp PDF hợp lệ.');

$service->delete($user, $copy['id']);
assertTrue(count($service->list($user)) === 1, 'Xóa mềm phải loại CV khỏi danh sách.');

echo "PASS: create, autosave-version, publish, duplicate, primary, XSS-escape, PDF, soft-delete" . PHP_EOL;
