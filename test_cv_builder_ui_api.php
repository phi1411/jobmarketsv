require_once __DIR__ . '/vendor/autoload.php';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

use JobMarket\Domain\AuthenticationService;
use JobMarket\Domain\CvBuilder\CvTemplateCatalog;
use JobMarket\Domain\CvBuilder\OnlineCvService;
use JobMarket\Http\Controllers\OnlineCvController;
use JobMarket\Http\Controllers\Web\CvTemplateWebController;
use JobMarket\Http\Controllers\Web\StudentController;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

echo "=================================================================\n";
echo "   CV BUILDER & TEMPLATE GALLERY UI & API TEST SUITE            \n";
echo "=================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $message) {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] {$message}\n";
        $passCount++;
    } else {
        echo " [FAIL] {$message}\n";
        $failCount++;
    }
}

try {
    // 1. Check Template Catalog
    $templates = CvTemplateCatalog::all();
    assertTest(count($templates) === 3, "Catalog có đúng 3 mẫu CV");
    $keys = array_column($templates, 'key');
    assertTest(in_array('student-simple', $keys) && in_array('student-modern', $keys) && in_array('ats-classic', $keys), "Chứa đủ student-simple, student-modern, ats-classic");
    $atsCount = count(array_filter($templates, fn($t) => $t['ats_friendly'] === true));
    assertTest($atsCount === 3, "Tất cả 3 mẫu đều hỗ trợ ats_friendly");

    $authRepo = new \JobMarket\Infrastructure\AuthenticationRepository();
    $authService = new AuthenticationService($authRepo);
    $testEmail = 'test_cv_builder_' . time() . '@example.com';
    $authService->register([
        'name' => 'Nguyễn Sinh Viên Test',
        'email' => $testEmail,
        'password' => 'Student@123456',
        'role' => 'student'
    ]);
    $login = $authService->loginDetails([
        'email' => $testEmail,
        'password' => 'Student@123456'
    ]);
    assertTest(!empty($login['user']), "Đăng ký và lấy thông tin tài khoản sinh viên thử nghiệm thành công");
    $studentUser = $login['user'];

    // 3. Test OnlineCvService & Controller
    $cvService = new OnlineCvService();
    $controller = new OnlineCvController($cvService);

    // Create CV
    $createReq = new Request([], ['title' => 'CV Thực Tập Backend', 'template_key' => 'student-simple', 'language' => 'vi'], [], [], [], ['REQUEST_METHOD' => 'POST']);
    $createReq->setUser($studentUser);
    $createRes = $controller->store($createReq);
    assertTest($createRes->getStatusCode() === Response::HTTP_CREATED, "POST /student/cvs trả về 201 Created");
    $createdCv = $createRes->getPayload()['data'] ?? [];
    $cvId = $createdCv['id'] ?? '';
    assertTest(!empty($cvId) && str_starts_with($cvId, 'cv-'), "ID CV được tạo với tiền tố cv- ({$cvId})");
    assertTest(($createdCv['version'] ?? 0) === 1, "Phiên bản khởi tạo ban đầu là 1");

    // List CVs
    $listReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET']);
    $listReq->setUser($studentUser);
    $listRes = $controller->index($listReq);
    assertTest($listRes->getStatusCode() === Response::HTTP_OK, "GET /student/cvs trả về 200 OK");
    $listData = $listRes->getPayload()['data'] ?? [];
    assertTest(count($listData) >= 1, "Danh sách CV của sinh viên chứa ít nhất 1 CV");

    // Update CV with autosave & expected_version: 1
    $updateReq = new Request([], [
        'expected_version' => 1,
        'title' => 'CV Thực Tập Backend - Cập Nhật',
        'content' => [
            'personal' => [
                'full_name' => 'Nguyễn Sinh Viên Test',
                'email' => $testEmail,
                'phone' => '0912345678',
                'job_title' => 'Backend Developer Intern'
            ],
            'summary' => 'Sinh viên đam mê lập trình và phát triển hệ thống web.',
            'skills' => [
                ['name' => 'PHP', 'level' => 'Khá'],
                ['name' => 'MySQL', 'level' => 'Cơ bản']
            ]
        ],
        'style' => [
            'accent_color' => '#2563eb',
            'font_family' => 'DejaVu Sans'
        ]
    ], [], [], [], ['REQUEST_METHOD' => 'PATCH']);
    $updateReq->setUser($studentUser);
    $updateRes = $controller->update($updateReq, $cvId);
    assertTest($updateRes->getStatusCode() === Response::HTTP_OK, "PATCH /student/cvs/{id} thành công với expected_version: 1");
    $updatedCv = $updateRes->getPayload()['data'] ?? [];
    assertTest(($updatedCv['version'] ?? 0) === 2, "Phiên bản tăng lên 2 sau khi lưu");
    assertTest(($updatedCv['completion_percent'] ?? 0) > 0, "Phần trăm hoàn thiện được cập nhật ({$updatedCv['completion_percent']}%)");

    // 4. Test 409 Conflict: send outdated expected_version: 1 (current is 2)
    $conflictReq = new Request([], [
        'expected_version' => 1, // Stale version!
        'title' => 'Cố tình ghi đè bản cũ'
    ], [], [], [], ['REQUEST_METHOD' => 'PATCH']);
    $conflictReq->setUser($studentUser);
    $threwConflict = false;
    try {
        $controller->update($conflictReq, $cvId);
    } catch (\Throwable $e) {
        if ($e->getCode() === Response::HTTP_CONFLICT) {
            $threwConflict = true;
            assertTest(true, "Ném ngoại lệ với mã HTTP 409 khi version không khớp ({$e->getMessage()})");
        } else {
            throw $e;
        }
    }
    assertTest($threwConflict, "Khóa lạc quan (Optimistic Locking) ngăn chặn ghi đè khi phát hiện xung đột");

    // 5. Test Duplicate
    $dupReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST']);
    $dupReq->setUser($studentUser);
    $dupRes = $controller->duplicate($dupReq, $cvId);
    assertTest($dupRes->getStatusCode() === Response::HTTP_CREATED, "POST /student/cvs/{id}/duplicate trả về 201 Created");
    $dupData = $dupRes->getPayload()['data'] ?? [];
    assertTest(!empty($dupData['id']) && $dupData['id'] !== $cvId, "CV nhân bản có ID mới khác ID gốc");
    assertTest($dupData['version'] === 1, "Bản sao bắt đầu với version 1");

    // 6. Test Activate for Application
    $actReq = new Request([], ['expected_version' => 2], [], [], [], ['REQUEST_METHOD' => 'POST']);
    $actReq->setUser($studentUser);
    $actRes = $controller->activate($actReq, $cvId);
    assertTest($actRes->getStatusCode() === Response::HTTP_OK, "POST /student/cvs/{id}/activate trả về 200 OK");
    $actData = $actRes->getPayload()['data'] ?? [];
    assertTest(!empty($actData['active_cv']), "Kích hoạt tạo thành công active_cv snapshot trong hồ sơ sinh viên");

    // 7. Test Visibility Toggle
    $currentVerAfterAct = (int)($actData['cv']['version'] ?? 3);
    $visReq = new Request([], ['is_public' => true, 'expected_version' => $currentVerAfterAct], [], [], [], ['REQUEST_METHOD' => 'PATCH']);
    $visReq->setUser($studentUser);
    $visRes = $controller->visibility($visReq, $cvId);
    assertTest($visRes->getStatusCode() === Response::HTTP_OK, "PATCH /student/cvs/{id}/visibility bật công khai trả về 200 OK");
    $visData = $visRes->getPayload()['data'] ?? [];
    assertTest(!empty($visData['is_public']) && !empty($visData['public_slug']), "CV công khai có public_slug hợp lệ ({$visData['public_slug']})");

    // 8. Test Preview HTML
    $prevReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET']);
    $prevReq->setUser($studentUser);
    $prevRes = $controller->preview($prevReq, $cvId);
    assertTest($prevRes->getStatusCode() === Response::HTTP_OK, "GET /student/cvs/{id}/preview trả về 200 OK");
    $htmlContent = $prevRes->getPayload();
    assertTest(str_contains($htmlContent, '<!doctype html>') && str_contains($htmlContent, 'Nguyễn Sinh Viên Test'), "Preview trả về HTML chuẩn chứa họ tên ứng viên");

    // 9. Test PDF Export
    $pdfReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET']);
    $pdfReq->setUser($studentUser);
    $pdfRes = $controller->export($pdfReq, $cvId);
    assertTest($pdfRes->getStatusCode() === Response::HTTP_OK, "GET /student/cvs/{id}/export.pdf trả về 200 OK");
    $pdfBytes = $pdfRes->getPayload();
    assertTest(str_starts_with($pdfBytes, '%PDF-'), "PDF Export trả về dữ liệu nhị phân chuẩn PDF bắt đầu bằng %PDF-");

    // 10. Test Web Views Rendering
    $templateWebCtrl = new CvTemplateWebController();
    $tplWebReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET', 'HTTP_ACCEPT' => 'text/html']);
    $tplWebRes = $templateWebCtrl->index($tplWebReq);
    assertTest($tplWebRes->getStatusCode() === Response::HTTP_OK, "Web /mau-cv-sinh-vien trả về 200 OK");
    assertTest(str_contains($tplWebRes->getPayload(), 'cv-templates-hero'), "View /mau-cv-sinh-vien render đúng container kho mẫu");

    $studentWebCtrl = new StudentController();
    $cvListWebReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET', 'HTTP_ACCEPT' => 'text/html']);
    $cvListWebRes = $studentWebCtrl->cvs($cvListWebReq);
    assertTest($cvListWebRes->getStatusCode() === Response::HTTP_OK, "Web /student/cvs trả về 200 OK");
    assertTest(str_contains($cvListWebRes->getPayload(), 'Quản Lý CV Online Của Bạn'), "View /student/cvs render đúng trang quản lý");

    $cvEditWebReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'GET', 'HTTP_ACCEPT' => 'text/html']);
    $cvEditWebRes = $studentWebCtrl->editCv($cvEditWebReq, $cvId);
    assertTest($cvEditWebRes->getStatusCode() === Response::HTTP_OK, "Web /student/cvs/{id}/edit trả về 200 OK");
    assertTest(str_contains($cvEditWebRes->getPayload(), 'cv-editor-wrapper'), "View /student/cvs/{id}/edit render đúng editor layout");

    // Clean up: delete test CVs
    $delReq = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'DELETE']);
    $delReq->setUser($studentUser);
    $controller->destroy($delReq, $cvId);
    if (!empty($dupData['id'])) {
        $controller->destroy($delReq, $dupData['id']);
    }
    assertTest(true, "Dọn dẹp xóa mềm CV thử nghiệm thành công");

} catch (\Throwable $e) {
    echo " [ERROR] Ngoại lệ: " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine() . "\n";
    $failCount++;
}

echo "\n-----------------------------------------------------------------\n";
echo "Kết quả: {$passCount} PASSED, {$failCount} FAILED.\n";
echo "-----------------------------------------------------------------\n";

if ($failCount > 0) {
    exit(1);
}
