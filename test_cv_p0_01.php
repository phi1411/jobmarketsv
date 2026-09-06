<?php

/**
 * CV-P0-01 Automated Isolated Regression & Security Test Suite
 *
 * Requirements tested:
 *  1. Fail-fast environment guards: APP_ENV=testing, DB_NAME=jobmarket_test, .env.testing required.
 *  2. Disposable isolated storage: test files saved to disposable temp dir, strictly isolated from production/dev.
 *  3. Guest rejection: 401 for unauthenticated GET/POST/DELETE /student/cv.
 *  4. Role rejection: 403 for company and admin attempting GET/POST/DELETE /student/cv.
 *     - Company & admin cannot change student active-CV metadata or private file.
 *  5. Ownership / Tampering protection: client cannot affect another user's CV via user_id / profile_id params.
 *  6. Upload validation:
 *     - Missing file -> 422
 *     - Non-PDF extension -> 422
 *     - Spoofed MIME/content (renamed txt/bin) -> 422
 *     - Empty file (0 bytes) -> 422
 *     - Oversized file (> 5 MB) -> 422
 *  7. Valid PDF upload:
 *     - Opaque server-side storage filename outside webroot
 *     - Safe owner metadata response (file_name, file_size, mime_type, uploaded_at)
 *     - No raw storage path leaked
 *     - Database student_profiles updated with matching metadata
 *  8. Replacement behavior:
 *     - Uploading a new CV updates active CV reference
 *     - Prior stored file remains intact for snapshots (CV-P0-02 requirement)
 *  9. Deletion behavior (DELETE /student/cv):
 *     - Removes active metadata from student_profiles
 *     - Removes the physical active file when unreferenced
 *     - Leaves prior replacement files intentionally retained for snapshots intact
 *     - Preserves existing legacy cv_url
 * 10. Physical storage failure consistency:
 *     - If storage quarantine/deletion fails, database is not left claiming the file was deleted
 * 11. Rollback-safe active CV deletion on persistence failure:
 *     - If metadata persistence fails after quarantine begins, file is restored from quarantine
 *     - Active CV metadata and physical file remain intact and accessible
 *     - Service/endpoint throws error rather than success
 * 12. Recoverable state on quarantine restore failure:
 *     - If restore fails after DB persistence failure, DB points to quarantine file (never a missing file)
 *     - Throws actionable error with details
 * 13. Rollback & rejection on quarantine purge failure:
 *     - If physical purge fails, DELETE does not return success
 *     - Restores original file and metadata to guarantee consistency
 * 14. Upload failure cleanup:
 *     - If metadata persistence fails, newly written file is deleted (no orphan file)
 *     - Pre-existing active CV is not altered
 * 15. Legacy external-CV URL path removal:
 *     - PUT/PATCH /student/profile ignores new client-supplied cv_url
 *     - Existing legacy cv_url in database remains preserved
 * 16. Public profile isolation:
 *     - Public profile response strictly omits all CV fields
 * 17. Complete fixture and test storage cleanup in finally.
 */

define("BASE_PATH", __DIR__);
require_once __DIR__ . "/vendor/autoload.php";

// Strict fail-fast: .env.testing MUST exist
$envTestingFile = __DIR__ . "/.env.testing";
if (!file_exists($envTestingFile)) {
    fwrite(STDERR, "FATAL: File '.env.testing' not found. Tests must run in isolated test environment.\n");
    exit(1);
}
$dotenv = Dotenv\Dotenv::createMutable(__DIR__, ".env.testing");
$dotenv->load();

use JobMarket\Domain\Cv\CvStorageService;
use JobMarket\Domain\Cv\StudentCvService;
use JobMarket\Domain\Profile\Profile;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Domain\ProfileService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Http\Controllers\StudentCvController;
use JobMarket\Http\Kernel;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\ProfileRepository;

// 1. Environment & Database Guard
$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
$config = Config::env();
$dbName = $config["dbname"] ?? "";

echo "=================================================================\n";
echo "   CV-P0-01 PRIVATE PDF UPLOAD FOUNDATION REGRESSION TEST SUITE  \n";
echo "=================================================================\n";

if ($appEnv !== "testing") {
    echo "FATAL: Test suite must run on 'testing' environment. Current APP_ENV: '{$appEnv}'.\n";
    exit(1);
}

if ($dbName !== "jobmarket_test") {
    echo "FATAL: Test suite must run against 'jobmarket_test'. Current DB_NAME: '{$dbName}'.\n";
    exit(1);
}

$db = new PDO(
    "mysql:dbname={$dbName};host={$config['host']};port={$config['port']}",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$runId = bin2hex(random_bytes(4));
$fixturePrefix = "test_cv_p0_01_{$runId}_";

// Dedicated disposable test storage directory
$testStorageDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jobmarket_test_cvs_{$runId}";
@mkdir($testStorageDir, 0755, true);
$_ENV['CV_STORAGE_PATH'] = $testStorageDir;
putenv("CV_STORAGE_PATH={$testStorageDir}");

// Track temporary test files to delete
$tempLocalFiles = [];

function createTestTempFile(string $content, string $suffix = '.tmp'): string {
    global $tempLocalFiles;
    $path = tempnam(sys_get_temp_dir(), 'cv_test_') . $suffix;
    file_put_contents($path, $content);
    $tempLocalFiles[] = $path;
    return $path;
}

function createDummyPdfContent(string $text = "CV-P0-01 Test Content"): string {
    return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n200\n%%EOF\n% " . $text;
}

// Cleanup callback
$cleanup = function () use ($db, $fixturePrefix, $testStorageDir, &$tempLocalFiles) {
    try {
        $db->exec("DELETE FROM student_profiles WHERE user_id LIKE '{$fixturePrefix}%' OR id LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM companies WHERE id LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM users WHERE email LIKE '{$fixturePrefix}%' OR id LIKE '{$fixturePrefix}%'");
    } catch (Throwable) {
    }

    foreach ($tempLocalFiles as $f) {
        if (file_exists($f)) {
            @unlink($f);
        }
    }

    if (is_dir($testStorageDir)) {
        $files = @scandir($testStorageDir) ?: [];
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..') {
                @unlink($testStorageDir . DIRECTORY_SEPARATOR . $f);
            }
        }
        @rmdir($testStorageDir);
    }
};

register_shutdown_function($cleanup);

function assertCondition(bool $condition, string $description): void {
    if (!$condition) {
        echo " [FAIL]\n";
        echo "ASSERTION FAILED: {$description}\n";
        exit(1);
    }
}

$testsPassed = 0;

try {
    // =========================================================================
    // TEST 1: Fail-fast Environment & Storage Guards
    // =========================================================================
    echo "Running Test 1: Fail-fast Environment & Storage Guards... ";
    assertCondition($appEnv === "testing", "APP_ENV must be 'testing'");
    assertCondition($dbName === "jobmarket_test", "DB must be 'jobmarket_test'");
    assertCondition(is_dir($testStorageDir), "Disposable test storage directory must exist");
    $storageService = new CvStorageService($testStorageDir);
    assertCondition($storageService->getStorageDir() === $testStorageDir, "CvStorageService resolves custom test storage path");
    echo "[PASS]\n";
    $testsPassed++;

    // Helper to create test user with active token
    $createUser = function (string $role, string $suffix) use ($db, $fixturePrefix): array {
        $id = "{$fixturePrefix}{$suffix}";
        $email = "{$fixturePrefix}{$suffix}@test.vn";
        $password = password_hash("Secret123!", PASSWORD_BCRYPT);
        $name = "User {$suffix}";

        $tokenPayload = [
            "id"    => $id,
            "email" => $email,
            "role"  => $role,
            "iat"   => time(),
            "exp"   => time() + 3600,
        ];
        $token = JWT::encode($tokenPayload);
        $expiresAt = date("Y-m-d H:i:s", time() + 3600);

        $stmt = $db->prepare(
            "INSERT INTO users (id, name, email, password, role, status, token, token_expires_at) 
             VALUES (?, ?, ?, ?, ?, 'active', ?, ?)"
        );
        $stmt->execute([$id, $name, $email, $password, $role, $token, $expiresAt]);

        return [
            "id"    => $id,
            "email" => $email,
            "name"  => $name,
            "role"  => $role,
            "token" => $token,
        ];
    };

    $student1 = $createUser("student", "std1");
    $student2 = $createUser("student", "std2");
    $companyUser = $createUser("company", "comp1");
    $adminUser = $createUser("admin", "adm1");

    $kernel = new Kernel();
    $cvService = new StudentCvService($storageService, new ProfileRepository(), $db);
    $cvController = new StudentCvController($cvService);

    // =========================================================================
    // TEST 2: Guest Rejection (Unauthenticated -> 401)
    // =========================================================================
    echo "Running Test 2: Guest Rejection (401)... ";

    // 2a. Direct Controller without user
    $guestReq = new Request([], [], [], [], ["REQUEST_METHOD" => "GET", "REQUEST_URI" => "/student/cv"]);
    $caughtAuth = false;
    try {
        $cvController->show($guestReq);
    } catch (AuthenticationException) {
        $caughtAuth = true;
    }
    assertCondition($caughtAuth, "Direct controller show without user must throw AuthenticationException");

    // 2b. Kernel pipeline without token
    unset($_SERVER['HTTP_AUTHORIZATION']);
    $guestKernelReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $res = $kernel->handler($guestKernelReq);
    assertCondition($res->getStatusCode() === 401, "Kernel GET /student/cv without token returns 401");

    // Guest POST upload
    $guestPostReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resPost = $kernel->handler($guestPostReq);
    assertCondition($resPost->getStatusCode() === 401, "Kernel POST /student/cv without token returns 401");

    // Guest DELETE
    $guestDelReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "DELETE",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resDel = $kernel->handler($guestDelReq);
    assertCondition($resDel->getStatusCode() === 401, "Kernel DELETE /student/cv without token returns 401");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 3: Non-student Role Rejection (Company / Admin -> 403)
    // =========================================================================
    echo "Running Test 3: Non-student Role Rejection (403 for GET, POST, DELETE)... ";

    // Seed student 1 with an active CV first to ensure company/admin cannot delete or touch it
    $seedPdfContent = createDummyPdfContent("Student 1 Seed CV");
    $seedPdfTemp = createTestTempFile($seedPdfContent, ".pdf");
    $seedFilename = $storageService->store($seedPdfTemp);
    $db->prepare(
        "INSERT INTO student_profiles (id, user_id, full_name, cv_storage_path, cv_original_name, cv_mime_type, cv_file_size, cv_uploaded_at)
         VALUES (?, ?, ?, ?, ?, 'application/pdf', ?, NOW())"
    )->execute(["prof_{$student1['id']}", $student1['id'], "Student One", $seedFilename, "Seed_CV.pdf", strlen($seedPdfContent)]);

    // 3a. Company GET
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$companyUser['token']}";
    $compShowReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    assertCondition($kernel->handler($compShowReq)->getStatusCode() === 403, "Company user GET /student/cv returns 403");

    // 3b. Company POST
    $compUploadReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    assertCondition($kernel->handler($compUploadReq)->getStatusCode() === 403, "Company user POST /student/cv returns 403");

    // 3c. Company DELETE (attempting to delete student's CV via injected params)
    $compDelReq = new Request([
        "user_id" => $student1['id']
    ], [
        "user_id" => $student1['id']
    ], [], [], [
        "REQUEST_METHOD" => "DELETE",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    assertCondition($kernel->handler($compDelReq)->getStatusCode() === 403, "Company user DELETE /student/cv returns 403");

    // 3d. Admin GET
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$adminUser['token']}";
    $admShowReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    assertCondition($kernel->handler($admShowReq)->getStatusCode() === 403, "Admin user GET /student/cv returns 403");

    // 3e. Admin POST
    $admUploadReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    assertCondition($kernel->handler($admUploadReq)->getStatusCode() === 403, "Admin user POST /student/cv returns 403");

    // 3f. Admin DELETE
    $admDelReq = new Request([
        "user_id" => $student1['id']
    ], [
        "user_id" => $student1['id']
    ], [], [], [
        "REQUEST_METHOD" => "DELETE",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    assertCondition($kernel->handler($admDelReq)->getStatusCode() === 403, "Admin user DELETE /student/cv returns 403");

    // 3g. Assert student 1 active-CV metadata and private file remain UNTOUCHED
    $stmtCheck = $db->prepare("SELECT cv_storage_path, cv_original_name FROM student_profiles WHERE user_id = ?");
    $stmtCheck->execute([$student1['id']]);
    $seededProfile = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    assertCondition($seededProfile["cv_storage_path"] === $seedFilename, "Student 1 active CV storage path unchanged by unauthorized requests");
    assertCondition($seededProfile["cv_original_name"] === "Seed_CV.pdf", "Student 1 active CV original name unchanged");
    assertCondition(file_exists($testStorageDir . DIRECTORY_SEPARATOR . $seedFilename), "Student 1 private CV file remains intact on disk");

    // Clean seed record for next tests
    $db->prepare("DELETE FROM student_profiles WHERE user_id = ?")->execute([$student1['id']]);
    $storageService->deleteFile($seedFilename);

    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 4: Upload Validation Failures (422)
    // =========================================================================
    echo "Running Test 4: Upload Validation Failures (422)... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";

    // 4a. Missing file
    $noFileReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resNoFile = $kernel->handler($noFileReq);
    assertCondition($resNoFile->getStatusCode() === 422, "Missing file upload returns 422");
    $payloadNoFile = $resNoFile->getPayload();
    assertCondition(isset($payloadNoFile["errors"]["cv_file"]), "Error details mention cv_file");

    // 4b. Non-PDF extension (.docx)
    $docxTemp = createTestTempFile("Mock docx content", ".docx");
    $docxReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "resume.docx",
            "type"     => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "tmp_name" => $docxTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => filesize($docxTemp),
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resDocx = $kernel->handler($docxReq);
    assertCondition($resDocx->getStatusCode() === 422, "Non-PDF file (.docx) returns 422");

    // 4c. Non-PDF extension (.png)
    $pngTemp = createTestTempFile("Mock png content", ".png");
    $pngReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "avatar.png",
            "type"     => "image/png",
            "tmp_name" => $pngTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => filesize($pngTemp),
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resPng = $kernel->handler($pngReq);
    assertCondition($resPng->getStatusCode() === 422, "Image file (.png) returns 422");

    // 4d. Spoofed PDF (plain text renamed to .pdf)
    $spoofTemp = createTestTempFile("Hello I am a plain text pretending to be PDF", ".pdf");
    $spoofReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "fake.pdf",
            "type"     => "application/pdf", // Fake browser MIME
            "tmp_name" => $spoofTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => filesize($spoofTemp),
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resSpoof = $kernel->handler($spoofReq);
    assertCondition($resSpoof->getStatusCode() === 422, "Spoofed text file with .pdf extension returns 422");

    // 4e. Empty file (0 bytes)
    $emptyTemp = createTestTempFile("", ".pdf");
    $emptyReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "empty.pdf",
            "type"     => "application/pdf",
            "tmp_name" => $emptyTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => 0,
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resEmpty = $kernel->handler($emptyReq);
    assertCondition($resEmpty->getStatusCode() === 422, "Empty file (0 bytes) returns 422");

    // 4f. Oversized file (> 5 MB)
    $oversizedContent = "%PDF-1.4\n" . str_repeat("A", (5 * 1024 * 1024) + 1024);
    $oversizedTemp = createTestTempFile($oversizedContent, ".pdf");
    $oversizedReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "large.pdf",
            "type"     => "application/pdf",
            "tmp_name" => $oversizedTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => filesize($oversizedTemp),
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resOversized = $kernel->handler($oversizedReq);
    assertCondition($resOversized->getStatusCode() === 422, "File > 5 MB returns 422");

    // Verify test storage remains completely empty after failed validations
    $filesInStorage = array_diff(scandir($testStorageDir), ['.', '..']);
    assertCondition(count($filesInStorage) === 0, "No files should persist in storage after failed validation");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 5: Successful Valid PDF Upload & Metadata Persistence
    // =========================================================================
    echo "Running Test 5: Successful Valid PDF Upload & Metadata Persistence... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";

    $validPdfContent = createDummyPdfContent("Nguyen Van A - Software Engineer CV");
    $validPdfTemp = createTestTempFile($validPdfContent, ".pdf");
    $validPdfSize = strlen($validPdfContent);

    $uploadReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "Nguyen_Van_A_CV.pdf",
            "type"     => "application/pdf",
            "tmp_name" => $validPdfTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => $validPdfSize,
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);

    $resUpload = $kernel->handler($uploadReq);
    assertCondition($resUpload->getStatusCode() === 200, "Valid PDF upload returns 200 OK");

    $upPayload = $resUpload->getPayload();
    assertCondition($upPayload["success"] === true, "Upload response success is true");
    $meta = $upPayload["data"];

    // Verify safe owner metadata
    assertCondition($meta["file_name"] === "Nguyen_Van_A_CV.pdf", "file_name matches original name");
    assertCondition($meta["file_size"] === $validPdfSize, "file_size matches uploaded bytes");
    assertCondition($meta["mime_type"] === "application/pdf", "mime_type is application/pdf");
    assertCondition(!empty($meta["uploaded_at"]), "uploaded_at timestamp is present");
    assertCondition(!isset($meta["cv_storage_path"]), "raw cv_storage_path must NEVER be exposed in response");
    assertCondition(!isset($meta["path"]), "raw path must NEVER be exposed in response");

    // Verify file in private storage
    $storedFiles = array_diff(scandir($testStorageDir), ['.', '..']);
    assertCondition(count($storedFiles) === 1, "Exactly one file exists in private test storage");
    $storedFilename = reset($storedFiles);
    assertCondition(str_ends_with($storedFilename, ".pdf"), "Stored filename ends with .pdf");
    assertCondition($storedFilename !== "Nguyen_Van_A_CV.pdf", "Stored filename must be opaque and not leak original name");
    assertCondition(filesize($testStorageDir . DIRECTORY_SEPARATOR . $storedFilename) === $validPdfSize, "Stored file size matches byte-for-byte");

    // Verify database record in student_profiles
    $stmtProf = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmtProf->execute([$student1['id']]);
    $dbProf = $stmtProf->fetch(PDO::FETCH_ASSOC);
    assertCondition(!empty($dbProf), "Student profile record exists in DB");
    assertCondition($dbProf["cv_storage_path"] === $storedFilename, "DB cv_storage_path matches opaque filename");
    assertCondition($dbProf["cv_original_name"] === "Nguyen_Van_A_CV.pdf", "DB cv_original_name matches original name");
    assertCondition($dbProf["cv_mime_type"] === "application/pdf", "DB cv_mime_type is application/pdf");
    assertCondition((int)$dbProf["cv_file_size"] === $validPdfSize, "DB cv_file_size matches");
    assertCondition(!empty($dbProf["cv_uploaded_at"]), "DB cv_uploaded_at is set");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 6: Ownership & Anti-Tampering Protection
    // =========================================================================
    echo "Running Test 6: Ownership & Anti-Tampering Protection... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";

    $tamperPdfContent = createDummyPdfContent("Tamper Test");
    $tamperPdfTemp = createTestTempFile($tamperPdfContent, ".pdf");

    $tamperReq = new Request([
        "user_id" => $student2['id'],
        "id"      => $student2['id'],
    ], [
        "user_id"         => $student2['id'],
        "id"              => $student2['id'],
        "cv_storage_path" => "injected_path.pdf",
    ], [], [
        "cv_file" => [
            "name"     => "tamper_attempt.pdf",
            "type"     => "application/pdf",
            "tmp_name" => $tamperPdfTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => strlen($tamperPdfContent),
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);

    $resTamper = $kernel->handler($tamperReq);
    assertCondition($resTamper->getStatusCode() === 200, "Upload proceeds using authenticated user context");

    // Student 2's profile must remain completely empty/null
    $stmtProf2 = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmtProf2->execute([$student2['id']]);
    $prof2 = $stmtProf2->fetch(PDO::FETCH_ASSOC);
    assertCondition(empty($prof2) || empty($prof2["cv_storage_path"]), "Student 2 must not be affected by Student 1's request params");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 7: Active CV Inspection (GET /student/cv)
    // =========================================================================
    echo "Running Test 7: Active CV Inspection (GET /student/cv)... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";
    $getReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resGet = $kernel->handler($getReq);
    assertCondition($resGet->getStatusCode() === 200, "GET /student/cv returns 200");
    $getPayload = $resGet->getPayload();
    assertCondition($getPayload["success"] === true, "GET /student/cv success is true");
    assertCondition(!empty($getPayload["data"]), "GET /student/cv data is not null for student with CV");
    assertCondition($getPayload["data"]["file_name"] === "tamper_attempt.pdf", "Returned active CV matches last upload");
    assertCondition(!isset($getPayload["data"]["cv_storage_path"]), "Never expose storage path in GET /student/cv");

    // Student 2 has no CV -> returns data: null
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student2['token']}";
    $getReq2 = new Request([], [], [], [], [
        "REQUEST_METHOD" => "GET",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resGet2 = $kernel->handler($getReq2);
    assertCondition($resGet2->getStatusCode() === 200, "GET /student/cv for student without CV returns 200");
    $getPayload2 = $resGet2->getPayload();
    assertCondition($getPayload2["data"] === null, "data is null when no CV uploaded");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 8: Replacement Leaves Prior File Intact for Snapshots
    // =========================================================================
    echo "Running Test 8: Replacement Leaves Prior File Intact for Snapshots... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";

    // Capture currently stored filename (file 1)
    $stmtProf1 = $db->prepare("SELECT cv_storage_path FROM student_profiles WHERE user_id = ?");
    $stmtProf1->execute([$student1['id']]);
    $priorFile1 = $stmtProf1->fetchColumn();
    assertCondition(!empty($priorFile1), "Prior stored filename 1 exists");

    // Upload replacement CV (file 2)
    $replaceContent = createDummyPdfContent("Nguyen Van A - Updated CV v2");
    $replaceTemp = createTestTempFile($replaceContent, ".pdf");
    $replaceSize = strlen($replaceContent);

    $replaceReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "Nguyen_Van_A_CV_v2.pdf",
            "type"     => "application/pdf",
            "tmp_name" => $replaceTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => $replaceSize,
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resReplace = $kernel->handler($replaceReq);
    assertCondition($resReplace->getStatusCode() === 200, "Replacement upload returns 200");
    $repData = $resReplace->getPayload()["data"];
    assertCondition($repData["file_name"] === "Nguyen_Van_A_CV_v2.pdf", "New active CV original name updated");

    // Check new DB reference
    $stmtProf1->execute([$student1['id']]);
    $activeFile2 = $stmtProf1->fetchColumn();
    assertCondition($activeFile2 !== $priorFile1, "New active CV uses a different opaque storage filename");

    // Critical CV-P0-02 precondition: prior file must remain intact in storage
    assertCondition(file_exists($testStorageDir . DIRECTORY_SEPARATOR . $priorFile1), "Prior replacement file 1 must remain intact on disk");
    assertCondition(file_exists($testStorageDir . DIRECTORY_SEPARATOR . $activeFile2), "Active replacement file 2 exists on disk");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 9: Deletion Behavior (Physically deletes active file, keeps prior replacement)
    // =========================================================================
    echo "Running Test 9: Deletion Behavior (DELETE /student/cv)... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";

    // Preset a legacy cv_url to verify it is NOT destructively purged
    $legacyUrl = "https://drive.google.com/file/d/legacy-test/view";
    $db->prepare("UPDATE student_profiles SET cv_url = ? WHERE user_id = ?")
       ->execute([$legacyUrl, $student1['id']]);

    $delReq = new Request([], [], [], [], [
        "REQUEST_METHOD" => "DELETE",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $resDel = $kernel->handler($delReq);
    assertCondition($resDel->getStatusCode() === 200, "DELETE /student/cv returns 200");

    // Verify student_profiles has reset active CV columns
    $stmtProfAfterDel = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmtProfAfterDel->execute([$student1['id']]);
    $profAfterDel = $stmtProfAfterDel->fetch(PDO::FETCH_ASSOC);

    assertCondition($profAfterDel["cv_storage_path"] === null, "cv_storage_path is null after delete");
    assertCondition($profAfterDel["cv_original_name"] === null, "cv_original_name is null after delete");
    assertCondition($profAfterDel["cv_mime_type"] === null, "cv_mime_type is null after delete");
    assertCondition($profAfterDel["cv_file_size"] === null, "cv_file_size is null after delete");
    assertCondition($profAfterDel["cv_uploaded_at"] === null, "cv_uploaded_at is null after delete");
    assertCondition($profAfterDel["cv_url"] === $legacyUrl, "Legacy cv_url is preserved after delete");

    // Verify physical file behavior:
    // 1. Active file ($activeFile2) MUST be physically removed
    assertCondition(!file_exists($testStorageDir . DIRECTORY_SEPARATOR . $activeFile2), "Active file must be physically removed from storage after DELETE");
    // 2. Prior replacement file ($priorFile1) MUST STILL EXIST for future snapshots
    assertCondition(file_exists($testStorageDir . DIRECTORY_SEPARATOR . $priorFile1), "Prior replacement file 1 MUST NOT be deleted during active CV delete");

    // GET /student/cv should now return null
    $resGetAfterDel = $kernel->handler($getReq);
    assertCondition($resGetAfterDel->getPayload()["data"] === null, "GET /student/cv returns data: null after deletion");

    // Clean up $priorFile1 and $storedFilename now that assertions are verified
    @unlink($testStorageDir . DIRECTORY_SEPARATOR . $priorFile1);
    if (!empty($storedFilename) && file_exists($testStorageDir . DIRECTORY_SEPARATOR . $storedFilename)) {
        @unlink($testStorageDir . DIRECTORY_SEPARATOR . $storedFilename);
    }
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 10: Physical Storage Failure During Deletion Guarantees DB Consistency
    // =========================================================================
    echo "Running Test 10: Physical Storage Failure During Deletion Guarantees DB Consistency... ";

    // Upload a new active CV for student 1
    $failDelPdfContent = createDummyPdfContent("Delete Failure Consistency Test");
    $failDelPdfTemp = createTestTempFile($failDelPdfContent, ".pdf");
    $storedFailDelFile = $storageService->store($failDelPdfTemp);

    $db->prepare(
        "UPDATE student_profiles SET cv_storage_path = ?, cv_original_name = 'Consistent_CV.pdf', cv_mime_type = 'application/pdf', cv_file_size = ?, cv_uploaded_at = NOW() WHERE user_id = ?"
    )->execute([$storedFailDelFile, strlen($failDelPdfContent), $student1['id']]);

    // Create a mock storage service that simulates storage failure during quarantine
    $failingStorageService = new class($testStorageDir) extends CvStorageService {
        public function quarantineFile(string $storagePath): ?string {
            throw new RuntimeException("Simulated filesystem quarantine failure");
        }
        public function deleteFile(string $storagePath): bool {
            return false; // Simulate OS-level permission / deletion failure
        }
    };

    $failingCvService = new StudentCvService($failingStorageService, new ProfileRepository(), $db);
    $caughtDelException = false;
    try {
        $failingCvService->deleteActiveCv($student1);
    } catch (RuntimeException $e) {
        $caughtDelException = true;
    }
    assertCondition($caughtDelException, "deleteActiveCv must throw RuntimeException when physical deletion/quarantine fails");

    // Crucial check: Database MUST NOT be left claiming the file was deleted!
    $stmtCheckFail = $db->prepare("SELECT cv_storage_path, cv_original_name FROM student_profiles WHERE user_id = ?");
    $stmtCheckFail->execute([$student1['id']]);
    $profCheckFail = $stmtCheckFail->fetch(PDO::FETCH_ASSOC);
    assertCondition($profCheckFail["cv_storage_path"] === $storedFailDelFile, "Database must NOT claim file was deleted if physical quarantine failed");
    assertCondition($profCheckFail["cv_original_name"] === "Consistent_CV.pdf", "Original metadata remains intact");
    assertCondition(file_exists($testStorageDir . DIRECTORY_SEPARATOR . $storedFailDelFile), "Physical file remains intact on disk");

    // Clean up
    @unlink($testStorageDir . DIRECTORY_SEPARATOR . $storedFailDelFile);
    $db->prepare("UPDATE student_profiles SET cv_storage_path = NULL, cv_original_name = NULL WHERE user_id = ?")->execute([$student1['id']]);
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 11: Rollback & Consistency on Metadata Persistence Failure During Deletion
    // =========================================================================
    echo "Running Test 11: Rollback & Consistency on Metadata Persistence Failure During Deletion... ";

    // Upload an active CV for student 1
    $rollbackPdfContent = createDummyPdfContent("Rollback Safety On Deletion DB Failure Test");
    $rollbackPdfTemp = createTestTempFile($rollbackPdfContent, ".pdf");
    $storedRollbackFile = $storageService->store($rollbackPdfTemp);

    $db->prepare(
        "UPDATE student_profiles SET cv_storage_path = ?, cv_original_name = 'Rollback_Test_CV.pdf', cv_mime_type = 'application/pdf', cv_file_size = ?, cv_uploaded_at = NOW() WHERE user_id = ?"
    )->execute([$storedRollbackFile, strlen($rollbackPdfContent), $student1['id']]);

    // Create a mock ProfileRepository whose upsert throws during active CV deletion
    $failingDelRepo = new class extends ProfileRepository {
        public function upsert(Profile $profile): void {
            throw new RuntimeException("Simulated database failure during active CV deletion");
        }
    };

    // Use REAL storageService so the quarantine rename actually executes on disk
    $serviceWithFailingDelRepo = new StudentCvService($storageService, $failingDelRepo, $db);

    $caughtRollbackException = false;
    try {
        $serviceWithFailingDelRepo->deleteActiveCv($student1);
    } catch (RuntimeException $e) {
        $caughtRollbackException = str_contains($e->getMessage(), "Simulated database failure");
    }
    assertCondition($caughtRollbackException, "deleteActiveCv must throw error rather than report success when metadata persistence fails");

    // Assert 1: The active CV metadata STILL exists in database
    $stmtCheckRollback = $db->prepare("SELECT cv_storage_path, cv_original_name, cv_mime_type, cv_file_size, cv_uploaded_at FROM student_profiles WHERE user_id = ?");
    $stmtCheckRollback->execute([$student1['id']]);
    $profRollback = $stmtCheckRollback->fetch(PDO::FETCH_ASSOC);
    assertCondition($profRollback["cv_storage_path"] === $storedRollbackFile, "Active CV storage path metadata must still exist in DB after failed deletion");
    assertCondition($profRollback["cv_original_name"] === "Rollback_Test_CV.pdf", "Active CV original name metadata must still exist in DB");
    assertCondition((int)$profRollback["cv_file_size"] === strlen($rollbackPdfContent), "Active CV file size must still exist in DB");
    assertCondition(!empty($profRollback["cv_uploaded_at"]), "Active CV uploaded_at must still exist in DB");

    // Assert 2: The original physical file was restored from quarantine and is STILL available on disk
    $restoredFilePath = $testStorageDir . DIRECTORY_SEPARATOR . $storedRollbackFile;
    assertCondition(file_exists($restoredFilePath), "Original active CV file must be restored and still available on disk");
    assertCondition(filesize($restoredFilePath) === strlen($rollbackPdfContent), "Restored file content and size match byte-for-byte");

    // Assert 3: No stranded quarantine files remain in storage
    $filesInStorage = array_diff(scandir($testStorageDir), ['.', '..']);
    assertCondition(count($filesInStorage) === 1 && in_array($storedRollbackFile, $filesInStorage), "Only the restored original file exists; no stranded quarantine files");

    // Assert 4: Endpoint / service inspection still reports the active CV rather than null
    $activeCvInfo = $serviceWithFailingDelRepo->getActiveCv($student1);
    assertCondition($activeCvInfo !== null, "Endpoint/service must still report active CV rather than null");
    assertCondition($activeCvInfo["file_name"] === "Rollback_Test_CV.pdf", "Active CV info matches original file");

    // Clean up
    @unlink($restoredFilePath);
    $db->prepare("UPDATE student_profiles SET cv_storage_path = NULL, cv_original_name = NULL WHERE user_id = ?")->execute([$student1['id']]);
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 12: Recoverable State on Quarantine Restore Failure
    // =========================================================================
    echo "Running Test 12: Recoverable State on Quarantine Restore Failure... ";

    // Upload an active CV for student 1
    $restoreFailPdfContent = createDummyPdfContent("Restore Failure Recoverable State Test");
    $restoreFailPdfTemp = createTestTempFile($restoreFailPdfContent, ".pdf");
    $storedRestoreFailFile = $storageService->store($restoreFailPdfTemp);

    $db->prepare(
        "UPDATE student_profiles SET cv_storage_path = ?, cv_original_name = 'Restore_Fail_CV.pdf', cv_mime_type = 'application/pdf', cv_file_size = ?, cv_uploaded_at = NOW() WHERE user_id = ?"
    )->execute([$storedRestoreFailFile, strlen($restoreFailPdfContent), $student1['id']]);

    // Create a mock storage service where restoreQuarantinedFile() fails
    $storageWithFailingRestore = new class($testStorageDir) extends CvStorageService {
        public function restoreQuarantinedFile(string $quarantineFilename, string $originalStoragePath): bool {
            return false; // Simulate failure to restore quarantined file back to original location
        }
    };

    // Create a mock ProfileRepository that throws during initial active CV deletion
    $failingDelRepo2 = new class extends ProfileRepository {
        public function upsert(Profile $profile): void {
            throw new RuntimeException("Simulated database failure during active CV deletion metadata clear");
        }
    };

    $serviceWithFailingRestore = new StudentCvService($storageWithFailingRestore, $failingDelRepo2, $db);

    $caughtRestoreFailException = false;
    try {
        $serviceWithFailingRestore->deleteActiveCv($student1);
    } catch (RuntimeException $e) {
        $caughtRestoreFailException = str_contains($e->getMessage(), "không thể hoàn tác tệp CV");
    }
    assertCondition($caughtRestoreFailException, "deleteActiveCv must throw actionable RuntimeException when restore fails");

    // Check DB: must NOT be pointing at missing $storedRestoreFailFile!
    $stmtCheckRestFail = $db->prepare("SELECT cv_storage_path, cv_original_name, cv_file_size FROM student_profiles WHERE user_id = ?");
    $stmtCheckRestFail->execute([$student1['id']]);
    $profCheckRestFail = $stmtCheckRestFail->fetch(PDO::FETCH_ASSOC);

    assertCondition($profCheckRestFail["cv_storage_path"] !== $storedRestoreFailFile, "DB must NOT point to missing original file path");
    assertCondition(str_starts_with($profCheckRestFail["cv_storage_path"], $storedRestoreFailFile . ".quarantine."), "DB points to the actual quarantined file path");
    assertCondition($profCheckRestFail["cv_original_name"] === "Restore_Fail_CV.pdf", "Original CV name is preserved");

    // Check disk: the physical file exists at the path recorded in DB!
    $quarantinePhysicalPath = $testStorageDir . DIRECTORY_SEPARATOR . $profCheckRestFail["cv_storage_path"];
    assertCondition(file_exists($quarantinePhysicalPath), "Physical file exists on disk at the path recorded in DB");
    assertCondition(filesize($quarantinePhysicalPath) === strlen($restoreFailPdfContent), "File content is preserved");

    // Clean up
    @unlink($quarantinePhysicalPath);
    $db->prepare("UPDATE student_profiles SET cv_storage_path = NULL, cv_original_name = NULL WHERE user_id = ?")->execute([$student1['id']]);
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 13: Rollback & Rejection on Quarantine Purge Failure
    // =========================================================================
    echo "Running Test 13: Rollback & Rejection on Quarantine Purge Failure... ";

    // Upload an active CV for student 1
    $purgeFailPdfContent = createDummyPdfContent("Purge Failure Rollback Test");
    $purgeFailPdfTemp = createTestTempFile($purgeFailPdfContent, ".pdf");
    $storedPurgeFailFile = $storageService->store($purgeFailPdfTemp);

    $db->prepare(
        "UPDATE student_profiles SET cv_storage_path = ?, cv_original_name = 'Purge_Fail_CV.pdf', cv_mime_type = 'application/pdf', cv_file_size = ?, cv_uploaded_at = NOW() WHERE user_id = ?"
    )->execute([$storedPurgeFailFile, strlen($purgeFailPdfContent), $student1['id']]);

    // Create a mock storage service where purgeQuarantinedFile() fails
    $storageWithFailingPurge = new class($testStorageDir) extends CvStorageService {
        public function purgeQuarantinedFile(string $quarantineFilename): bool {
            return false; // Simulate physical file unlink failure
        }
    };

    // Use REAL ProfileRepository so that metadata clearing succeeds and rollback can be tested
    $serviceWithFailingPurge = new StudentCvService($storageWithFailingPurge, new ProfileRepository(), $db);

    $caughtPurgeFailException = false;
    try {
        $serviceWithFailingPurge->deleteActiveCv($student1);
    } catch (RuntimeException $e) {
        $caughtPurgeFailException = str_contains($e->getMessage(), "Không thể xóa hoàn toàn tệp tin CV vật lý");
    }
    assertCondition($caughtPurgeFailException, "deleteActiveCv must throw error rather than report success when purge fails");

    // Check DB: original metadata MUST be restored
    $stmtCheckPurge = $db->prepare("SELECT cv_storage_path, cv_original_name, cv_file_size, cv_uploaded_at FROM student_profiles WHERE user_id = ?");
    $stmtCheckPurge->execute([$student1['id']]);
    $profCheckPurge = $stmtCheckPurge->fetch(PDO::FETCH_ASSOC);

    assertCondition($profCheckPurge["cv_storage_path"] === $storedPurgeFailFile, "DB active CV path must be restored to original file");
    assertCondition($profCheckPurge["cv_original_name"] === "Purge_Fail_CV.pdf", "DB active CV original name must be restored");
    assertCondition((int)$profCheckPurge["cv_file_size"] === strlen($purgeFailPdfContent), "DB active CV file size must be restored");
    assertCondition(!empty($profCheckPurge["cv_uploaded_at"]), "DB active CV uploaded_at must be preserved");

    // Check disk: original physical file was restored and exists on disk
    $restoredPurgeFilePath = $testStorageDir . DIRECTORY_SEPARATOR . $storedPurgeFailFile;
    assertCondition(file_exists($restoredPurgeFilePath), "Original physical file must be restored and still exist on disk");
    assertCondition(filesize($restoredPurgeFilePath) === strlen($purgeFailPdfContent), "Restored file content and size match byte-for-byte");

    // Check inspection: getActiveCv still returns the active CV
    $activeCvPurgeInfo = $serviceWithFailingPurge->getActiveCv($student1);
    assertCondition($activeCvPurgeInfo !== null, "getActiveCv must still return active CV after purge rollback");
    assertCondition($activeCvPurgeInfo["file_name"] === "Purge_Fail_CV.pdf", "Active CV info matches original file");

    // Clean up
    @unlink($restoredPurgeFilePath);
    $db->prepare("UPDATE student_profiles SET cv_storage_path = NULL, cv_original_name = NULL WHERE user_id = ?")->execute([$student1['id']]);
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 14: Cleanup After Simulated Upload Metadata Persistence Failure
    // =========================================================================
    echo "Running Test 14: Cleanup After Simulated Upload Metadata Persistence Failure... ";

    // Set up student 1 with a pre-existing active CV
    $preExistingPdfContent = createDummyPdfContent("Pre-existing Active CV");
    $preExistingPdfTemp = createTestTempFile($preExistingPdfContent, ".pdf");
    $preExistingStoredFile = $storageService->store($preExistingPdfTemp);

    $db->prepare(
        "UPDATE student_profiles SET cv_storage_path = ?, cv_original_name = 'Original_Active_CV.pdf', cv_mime_type = 'application/pdf', cv_file_size = ?, cv_uploaded_at = NOW() WHERE user_id = ?"
    )->execute([$preExistingStoredFile, strlen($preExistingPdfContent), $student1['id']]);

    // Create a mock ProfileRepository whose upsert fails
    $failingRepo = new class extends ProfileRepository {
        public function upsert(Profile $profile): void {
            throw new RuntimeException("Simulated database failure during profile upsert");
        }
    };

    $serviceWithFailingRepo = new StudentCvService($storageService, $failingRepo, $db);

    $newUploadPdfContent = createDummyPdfContent("New Attempt That Will Fail DB Save");
    $newUploadPdfTemp = createTestTempFile($newUploadPdfContent, ".pdf");

    $caughtUpsertFailure = false;
    try {
        $serviceWithFailingRepo->uploadActiveCv($student1, [
            "name"     => "Failed_Upload.pdf",
            "type"     => "application/pdf",
            "tmp_name" => $newUploadPdfTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => strlen($newUploadPdfContent),
        ]);
    } catch (RuntimeException $e) {
        $caughtUpsertFailure = str_contains($e->getMessage(), "Simulated database failure");
    }
    assertCondition($caughtUpsertFailure, "uploadActiveCv must rethrow the original metadata persistence failure");

    // Check storage: ONLY the pre-existing file must exist, the new file must have been deleted (no orphan file)
    $remainingFilesInStorage = array_diff(scandir($testStorageDir), ['.', '..']);
    assertCondition(count($remainingFilesInStorage) === 1, "Only one file should remain in storage; orphan newly written file must be cleaned up");
    assertCondition(in_array($preExistingStoredFile, $remainingFilesInStorage), "Pre-existing file must remain intact");

    // Check DB: pre-existing active CV metadata is completely unchanged
    $stmtProfPre = $db->prepare("SELECT cv_storage_path, cv_original_name FROM student_profiles WHERE user_id = ?");
    $stmtProfPre->execute([$student1['id']]);
    $profPre = $stmtProfPre->fetch(PDO::FETCH_ASSOC);
    assertCondition($profPre["cv_storage_path"] === $preExistingStoredFile, "Pre-existing active CV path in DB must be unaltered");
    assertCondition($profPre["cv_original_name"] === "Original_Active_CV.pdf", "Pre-existing active CV original name in DB must be unaltered");

    // Clean up
    @unlink($testStorageDir . DIRECTORY_SEPARATOR . $preExistingStoredFile);
    $db->prepare("UPDATE student_profiles SET cv_storage_path = NULL, cv_original_name = NULL WHERE user_id = ?")->execute([$student1['id']]);
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 15: Rejection of New Client-Supplied cv_url via PUT/PATCH
    // =========================================================================
    echo "Running Test 15: Rejection of New Client-Supplied cv_url via PUT/PATCH... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";

    // Set a legacy cv_url in database
    $originalLegacyUrl = "https://drive.google.com/file/d/legacy-original-123/view";
    $db->prepare("UPDATE student_profiles SET cv_url = ? WHERE user_id = ?")
       ->execute([$originalLegacyUrl, $student1['id']]);

    // Student attempts to change cv_url via PUT /student/profile
    $putReq = new Request([], [
        "full_name" => "Nguyen Van A New Name",
        "cv_url"    => "https://attacker.example/malicious_cv.pdf",
    ], [], [], [
        "REQUEST_METHOD" => "PUT",
        "REQUEST_URI"    => "/student/profile",
        "HTTP_ACCEPT"    => "application/json",
        "CONTENT_TYPE"   => "application/json",
    ]);

    $resPut = $kernel->handler($putReq);
    assertCondition($resPut->getStatusCode() === 200, "Profile update returns 200");

    // Verify DB still holds the original legacy cv_url, NOT the attacker/client-supplied one
    $stmtCheckUrl = $db->prepare("SELECT cv_url, full_name FROM student_profiles WHERE user_id = ?");
    $stmtCheckUrl->execute([$student1['id']]);
    $profCheckUrl = $stmtCheckUrl->fetch(PDO::FETCH_ASSOC);
    assertCondition($profCheckUrl["full_name"] === "Nguyen Van A New Name", "Valid fields (full_name) are updated");
    assertCondition($profCheckUrl["cv_url"] === $originalLegacyUrl, "Legacy cv_url must NOT be changed by client input in PUT /student/profile");

    // For student 2 without cv_url, supplying cv_url must remain null
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student2['token']}";
    $putReq2 = new Request([], [
        "full_name" => "Student Two Name",
        "cv_url"    => "https://topcv.example/student2-cv.pdf",
    ], [], [], [
        "REQUEST_METHOD" => "PUT",
        "REQUEST_URI"    => "/student/profile",
        "HTTP_ACCEPT"    => "application/json",
        "CONTENT_TYPE"   => "application/json",
    ]);
    $resPut2 = $kernel->handler($putReq2);
    assertCondition($resPut2->getStatusCode() === 200, "Student 2 profile update returns 200");

    $stmtCheckUrl2 = $db->prepare("SELECT cv_url FROM student_profiles WHERE user_id = ?");
    $stmtCheckUrl2->execute([$student2['id']]);
    $cvUrl2 = $stmtCheckUrl2->fetchColumn();
    assertCondition($cvUrl2 === null, "New client-supplied cv_url for student without legacy URL must remain NULL");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 16: Public Profile Isolation Guarantee
    // =========================================================================
    echo "Running Test 16: Public Profile Isolation Guarantee... ";
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$student1['token']}";

    // Upload a fresh CV for student 1
    $freshPdfContent = createDummyPdfContent("Fresh CV for public test");
    $freshPdfTemp = createTestTempFile($freshPdfContent, ".pdf");

    $freshUpReq = new Request([], [], [], [
        "cv_file" => [
            "name"     => "Public_Isolation_CV.pdf",
            "type"     => "application/pdf",
            "tmp_name" => $freshPdfTemp,
            "error"    => UPLOAD_ERR_OK,
            "size"     => strlen($freshPdfContent),
        ]
    ], [
        "REQUEST_METHOD" => "POST",
        "REQUEST_URI"    => "/student/cv",
        "HTTP_ACCEPT"    => "application/json",
    ]);
    $kernel->handler($freshUpReq);

    // Call public profile retrieval
    $profileService = new ProfileService(new ProfileRepository());
    $publicProfile = $profileService->getPublicProfile($student1['id']);

    assertCondition(!array_key_exists("cv_storage_path", $publicProfile), "Public profile must NEVER contain cv_storage_path");
    assertCondition(!array_key_exists("cv_original_name", $publicProfile), "Public profile must NEVER contain cv_original_name");
    assertCondition(!array_key_exists("cv_mime_type", $publicProfile), "Public profile must NEVER contain cv_mime_type");
    assertCondition(!array_key_exists("cv_file_size", $publicProfile), "Public profile must NEVER contain cv_file_size");
    assertCondition(!array_key_exists("cv_uploaded_at", $publicProfile), "Public profile must NEVER contain cv_uploaded_at");
    assertCondition(!array_key_exists("active_cv", $publicProfile), "Public profile must NEVER contain active_cv");
    assertCondition(!array_key_exists("cv_url", $publicProfile), "Public profile must NEVER contain cv_url");

    // Also verify Profile::toArrayPublic directly
    $stmtProfFetch = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmtProfFetch->execute([$student1['id']]);
    $rawProf = $stmtProfFetch->fetch(PDO::FETCH_ASSOC);
    $profileEntity = Profile::fromArray($rawProf);
    $entityPublic = $profileEntity->toArrayPublic();

    assertCondition(!array_key_exists("cv_storage_path", $entityPublic), "toArrayPublic() must NOT include cv_storage_path");
    assertCondition(!array_key_exists("cv_original_name", $entityPublic), "toArrayPublic() must NOT include cv_original_name");
    assertCondition(!array_key_exists("active_cv", $entityPublic), "toArrayPublic() must NOT include active_cv");
    assertCondition(!array_key_exists("cv_url", $entityPublic), "toArrayPublic() must NOT include cv_url");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // Summary
    // =========================================================================
    echo "\n=================================================================\n";
    echo "  ALL {$testsPassed} CV-P0-01 REGRESSION TESTS PASSED SUCCESSFULLY!  \n";
    echo "=================================================================\n";

} catch (Throwable $e) {
    echo " [FAIL]\n";
    echo "UNHANDLED EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
} finally {
    $cleanup();
}
