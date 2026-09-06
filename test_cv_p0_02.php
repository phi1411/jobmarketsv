<?php

/**
 * CV-P0-02 Automated Isolated Regression & Security Test Suite
 *
 * Requirements tested:
 *  1. Fail-fast environment guards: APP_ENV=testing, DB_NAME=jobmarket_test.
 *  2. Disposable isolated storage: test files saved to disposable temp dir outside webroot.
 *  3. Apply without active uploaded CV -> 422, Vietnamese validation message, no application/notification.
 *  4. Apply with client-supplied external CV params -> client fields ignored/rejected, server-owned active CV required.
 *  5. Apply with active profile referencing missing file on disk -> 422, no application/notification.
 *  6. Apply with valid active uploaded CV -> 201, application stores immutable snapshot, company notification created.
 *     - Response omits raw storage path (has_cv_snapshot, cv_file_name, etc.)
 *     - Applications have NULL resume and NULL cv_url_snapshot (no external CV URLs copied).
 *  7. Authorized student retrieves own snapshot via GET /applications/{id}/cv -> 200, Content-Type, Content-Disposition, nosniff, private cache, content matches.
 *  8. Authorized company owning job retrieves applicant snapshot via GET /applications/{id}/cv -> 200, content matches.
 *  9. Another student retrieves application CV -> 403 Forbidden.
 * 10. Another company retrieves application CV -> 403 Forbidden.
 * 11. Admin retrieves application CV -> 403 Forbidden.
 * 12. Unauthenticated guest retrieves application CV -> 401 Unauthorized.
 * 13. Non-existent application ID -> 404 Not Found.
 * 14. Application exists but physical file deleted from storage -> 404 Not Found.
 * 15. Student replaces active profile CV after applying -> older snapshot remains available and unchanged.
 * 16. Student deletes active profile CV after applying -> physical file retained due to snapshot reference, snapshot remains available.
 * 17. Student with both legacy profile cv_url and valid private uploaded CV -> snapshot persists, resume and cv_url_snapshot are NULL in DB and API response.
 * 18. Legacy application records preserved without destructive modification -> existing resume/cv_url_snapshot remain readable.
 * 19. Duplicate application prevention -> 409 Conflict.
 * 20. Complete fixture and test storage cleanup in finally / shutdown.
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

use JobMarket\Domain\Application\Application;
use JobMarket\Domain\ApplicationService;
use JobMarket\Domain\Cv\CvStorageService;
use JobMarket\Domain\Cv\StudentCvService;
use JobMarket\Domain\Profile\Profile;
use JobMarket\Facades\Config;
use JobMarket\Facades\JWT;
use JobMarket\Http\Controllers\ApplicationController;
use JobMarket\Http\Kernel;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\ApplicationRepository;
use JobMarket\Infrastructure\CompanyRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Infrastructure\ProfileRepository;

// 1. Environment & Database Guard
$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
$config = Config::env();
$dbName = $config["dbname"] ?? "";

echo "=================================================================\n";
echo "   CV-P0-02 SERVER-OWNED APPLICATION SNAPSHOT REGRESSION SUITE  \n";
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
$fixturePrefix = "test_p02_{$runId}_";

// Dedicated disposable test storage directory
$testStorageDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jobmarket_test_cvs_p02_{$runId}";
@mkdir($testStorageDir, 0755, true);
$_ENV['CV_STORAGE_PATH'] = $testStorageDir;
putenv("CV_STORAGE_PATH={$testStorageDir}");

// Track temporary test files to delete
$tempLocalFiles = [];

function createTestTempPdf(string $uniqueMarker = "test"): string {
    global $tempLocalFiles;
    $path = tempnam(sys_get_temp_dir(), 'cv_p02_') . '.pdf';
    $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n200\n%%EOF\n% CV-P0-02 Marker: " . $uniqueMarker;
    file_put_contents($path, $content);
    $tempLocalFiles[] = $path;
    return $path;
}

// Cleanup callback
$cleanup = function () use ($db, $fixturePrefix, $testStorageDir, &$tempLocalFiles) {
    try {
        $db->exec("DELETE FROM notifications WHERE user_id LIKE '{$fixturePrefix}%' OR id LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM applications WHERE id LIKE '{$fixturePrefix}%' OR developer_id LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM jobs WHERE id LIKE '{$fixturePrefix}%' OR company_id LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM companies WHERE id LIKE '{$fixturePrefix}%' OR user_id LIKE '{$fixturePrefix}%'");
        $db->exec("DELETE FROM student_profiles WHERE user_id LIKE '{$fixturePrefix}%' OR id LIKE '{$fixturePrefix}%'");
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
    // TEST 1: Environment & Disposable Storage Guards
    // =========================================================================
    echo "Running Test 1: Fail-fast Environment & Storage Guards... ";
    assertCondition($appEnv === "testing", "APP_ENV must be 'testing'");
    assertCondition($dbName === "jobmarket_test", "DB must be 'jobmarket_test'");
    assertCondition(is_dir($testStorageDir), "Disposable test storage directory must exist");
    $storageService = new CvStorageService($testStorageDir);
    assertCondition($storageService->fileExists("non_existent.pdf") === false, "Storage correctly checks fileExists");
    echo "[PASS]\n";
    $testsPassed++;

    // Helper to create users
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

    // Create test accounts
    $student1 = $createUser("student", "std1");
    $student2 = $createUser("student", "std2");
    $comp1User = $createUser("company", "comp1_user");
    $comp2User = $createUser("company", "comp2_user");
    $adminUser = $createUser("admin", "adm1");

    // Create companies
    $createCompany = function (string $suffix, array $user) use ($db, $fixturePrefix): array {
        $id = "{$fixturePrefix}{$suffix}";
        $stmt = $db->prepare(
            "INSERT INTO companies (id, user_id, name, description, verification_status, created_at)
             VALUES (?, ?, ?, ?, 'verified', NOW())"
        );
        $stmt->execute([$id, $user["id"], "Company {$suffix}", "Description for Company {$suffix}"]);
        return ["id" => $id, "user_id" => $user["id"], "name" => "Company {$suffix}"];
    };

    $comp1 = $createCompany("comp1", $comp1User);
    $comp2 = $createCompany("comp2", $comp2User);

    // Create jobs
    $createJob = function (string $suffix, array $company) use ($db, $fixturePrefix): array {
        $id = "{$fixturePrefix}{$suffix}";
        $stmt = $db->prepare(
            "INSERT INTO jobs (id, company_id, title, status, shift_type, salary_min, salary_max, application_deadline, created_at)
             VALUES (?, ?, ?, 'published', 'morning', 20000, 30000, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())"
        );
        $stmt->execute([$id, $company["id"], "Job {$suffix}"]);
        return ["id" => $id, "company_id" => $company["id"], "title" => "Job {$suffix}"];
    };

    $job1 = $createJob("job1", $comp1);
    $job2 = $createJob("job2", $comp2);

    $kernel = new Kernel();

    // Helper to send HTTP requests to Kernel
    $makeRequest = function (string $method, string $path, array $data = [], ?string $token = null) use ($kernel): Response {
        $server = [
            "REQUEST_METHOD" => $method,
            "REQUEST_URI"    => $path,
            "HTTP_ACCEPT"    => "application/json",
        ];
        if ($token !== null) {
            $server["HTTP_AUTHORIZATION"] = "Bearer {$token}";
            $_SERVER["HTTP_AUTHORIZATION"] = "Bearer {$token}";
        } else {
            unset($_SERVER["HTTP_AUTHORIZATION"]);
        }

        $getParams = ($method === "GET") ? $data : [];
        $postParams = ($method !== "GET") ? $data : [];

        $request = new Request($getParams, $postParams, [], [], $server);
        return $kernel->handler($request);
    };

    // =========================================================================
    // TEST 2: Apply without active uploaded CV -> 422 & no application/notification (Case 1)
    // =========================================================================
    echo "Running Test 2: Apply without active uploaded CV (Case 1)... ";
    $res = $makeRequest("POST", "/jobs/{$job1['id']}/applications", [
        "preferred_shift" => "morning",
        "cover_letter"    => "Em muốn ứng tuyển ca sáng."
    ], $student1["token"]);

    assertCondition($res->getStatusCode() === 422, "Applying without active CV must return 422. Got: {$res->getStatusCode()}");
    $payload = $res->getPayload();
    assertCondition(isset($payload["errors"]["cv_file"]), "422 response must contain 'cv_file' validation error");
    $cvErrorMsg = implode(" ", (array)($payload["errors"]["cv_file"] ?? []));
    assertCondition(str_contains($cvErrorMsg, "Bạn chưa có CV tải lên"), "Error message must guide user to upload CV in Vietnamese");

    // Verify no application record in DB
    $stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ? AND developer_id = ?");
    $stmt->execute([$job1["id"], $student1["id"]]);
    assertCondition((int)$stmt->fetchColumn() === 0, "No application must be persisted on 422");

    // Verify no notification created
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$comp1User["id"]]);
    assertCondition((int)$stmt->fetchColumn() === 0, "No notification must be sent on 422");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 3: Apply with client-supplied external CV params -> Ignored & still 422 (Case 2)
    // =========================================================================
    echo "Running Test 3: Apply with client-supplied CV parameters (Case 2)... ";
    $res = $makeRequest("POST", "/jobs/{$job1['id']}/applications", [
        "preferred_shift" => "morning",
        "cover_letter"    => "Em gửi kèm link",
        "cv_url_snapshot" => "https://malicious.example.com/fake_cv.pdf",
        "resume"          => "https://malicious.example.com/resume.pdf",
        "cv_storage_path" => "/etc/passwd",
        "file_id"         => "malicious_file_123",
        "user_id"         => $student2["id"],
    ], $student1["token"]);

    assertCondition($res->getStatusCode() === 422, "Client cannot bypass active profile CV requirement by passing external parameters. Got: {$res->getStatusCode()}");
    $stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ? AND developer_id = ?");
    $stmt->execute([$job1["id"], $student1["id"]]);
    assertCondition((int)$stmt->fetchColumn() === 0, "No application created when client supplies spoofed CV fields");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 4: Apply when profile points to missing file on disk -> 422 (Case 4)
    // =========================================================================
    echo "Running Test 4: Apply with missing physical file referenced in profile (Case 4)... ";
    $profileRepo = new ProfileRepository();
    $p = new Profile($student1["id"]);
    $p->setCvStoragePath("phantom_missing_file.pdf");
    $p->setCvOriginalName("phantom.pdf");
    $p->setCvFileSize(12345);
    $p->setCvMimeType("application/pdf");
    $profileRepo->upsert($p);

    $res = $makeRequest("POST", "/jobs/{$job1['id']}/applications", [
        "preferred_shift" => "morning"
    ], $student1["token"]);

    assertCondition($res->getStatusCode() === 422, "Apply with missing disk file must return 422. Got: {$res->getStatusCode()}");
    $payload = $res->getPayload();
    assertCondition(isset($payload["errors"]["cv_file"]), "422 response must contain cv_file error");
    $stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ? AND developer_id = ?");
    $stmt->execute([$job1["id"], $student1["id"]]);
    assertCondition((int)$stmt->fetchColumn() === 0, "No application record on missing file error");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 5: Apply with valid active uploaded CV -> 201 Created & snapshot stored (Case 3)
    // =========================================================================
    echo "Running Test 5: Apply with valid active uploaded CV (Case 3)... ";
    $cvService = new StudentCvService($storageService, $profileRepo, $db);
    $tmpPdf1 = createTestTempPdf("PDF-CONTENT-STUDENT-1-VERSION-1");
    $uploadRes = $cvService->uploadActiveCv($student1, [
        "name"     => "my_cv_v1.pdf",
        "tmp_name" => $tmpPdf1,
        "size"     => filesize($tmpPdf1),
        "error"    => UPLOAD_ERR_OK,
    ]);
    assertCondition(!empty($uploadRes["file_name"]), "Student 1 uploaded active CV successfully");

    // Fetch storage filename from DB
    $std1Profile = $profileRepo->findByUserId($student1["id"]);
    $activeCvPathV1 = $std1Profile["cv_storage_path"];
    assertCondition(!empty($activeCvPathV1), "Student 1 has active cv_storage_path in profile");
    assertCondition($storageService->fileExists($activeCvPathV1), "Active CV file exists in test storage");

    // Apply with client also trying to pass external resume
    $res = $makeRequest("POST", "/jobs/{$job1['id']}/applications", [
        "preferred_shift" => "morning",
        "cover_letter"    => "Kính gửi nhà tuyển dụng Company 1",
        "resume"          => "https://attacker.com/overridden.pdf", // Must be ignored
        "cv_url_snapshot" => "https://attacker.com/overridden2.pdf", // Must be ignored
    ], $student1["token"]);

    assertCondition($res->getStatusCode() === 201, "Applying with active CV returns 201 Created. Got: {$res->getStatusCode()}");
    $appData = $res->getPayload()["data"] ?? [];
    assertCondition(!empty($appData["id"]), "Application ID returned");
    $app1Id = $appData["id"];

    // Verify response does NOT leak raw cv_storage_path
    assertCondition(!isset($appData["cv_storage_path"]), "Response to student MUST NOT leak raw cv_storage_path");
    assertCondition(($appData["has_cv_snapshot"] ?? false) === true, "Response contains has_cv_snapshot = true");
    assertCondition(($appData["cv_file_name"] ?? "") === "my_cv_v1.pdf", "Response contains safe cv_file_name");

    // Finding 1 assertion: resume and cv_url_snapshot MUST be null in new application response
    assertCondition($appData["resume"] === null, "API response resume must be NULL for new applications");
    assertCondition($appData["cv_url_snapshot"] === null, "API response cv_url_snapshot must be NULL for new applications");

    // Verify database record has immutable snapshot and NULL resume
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$app1Id]);
    $dbApp1 = $stmt->fetch(PDO::FETCH_ASSOC);
    assertCondition($dbApp1 !== false, "Application record exists in database");
    assertCondition($dbApp1["cv_storage_path"] === $activeCvPathV1, "cv_storage_path matches student active file");
    assertCondition($dbApp1["cv_original_name"] === "my_cv_v1.pdf", "cv_original_name matches uploaded filename");
    assertCondition((int)$dbApp1["cv_file_size"] === filesize($tmpPdf1), "cv_file_size matches file size");
    assertCondition($dbApp1["cv_mime_type"] === "application/pdf", "cv_mime_type is application/pdf");
    assertCondition($dbApp1["resume"] === null, "Database resume column MUST be NULL for new applications");

    // Verify notification was sent to Company 1 user
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$comp1User["id"]]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    assertCondition($notif !== false, "Notification created for company owner");
    assertCondition(str_contains($notif["title"], "Đơn ứng tuyển mới"), "Notification title matches");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 6: Student retrieves own snapshot via GET /applications/{id}/cv (Case 5)
    // =========================================================================
    echo "Running Test 6: Authorized student retrieves own snapshot (Case 5)... ";
    $res = $makeRequest("GET", "/applications/{$app1Id}/cv", [], $student1["token"]);
    assertCondition($res->getStatusCode() === 200, "Student retrieving own CV returns 200. Got: {$res->getStatusCode()}");
    assertCondition($res->getHeader("Content-Type") === "application/pdf", "Content-Type must be application/pdf");
    assertCondition($res->getHeader("X-Content-Type-Options") === "nosniff", "X-Content-Type-Options must be nosniff");
    assertCondition(str_contains((string)$res->getHeader("Cache-Control"), "private"), "Cache-Control must be private");
    assertCondition(str_contains((string)$res->getHeader("Content-Disposition"), "my_cv_v1.pdf"), "Content-Disposition must contain original filename");
    assertCondition($res->isFile() === true, "Response is marked as file response");
    assertCondition(file_exists((string)$res->getFilePath()), "Response file path points to existing file");

    // Verify content matches original file without headers-already-sent warning
    $fileContent = file_get_contents($res->getFilePath());
    assertCondition(str_contains($fileContent, "PDF-CONTENT-STUDENT-1-VERSION-1"), "Protected response file content matches original uploaded PDF");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 7: Authorized company retrieves applicant snapshot (Case 6)
    // =========================================================================
    echo "Running Test 7: Authorized company retrieves applicant snapshot (Case 6)... ";
    $res = $makeRequest("GET", "/applications/{$app1Id}/cv", [], $comp1User["token"]);
    assertCondition($res->getStatusCode() === 200, "Authorized company retrieving applicant CV returns 200. Got: {$res->getStatusCode()}");
    assertCondition($res->getHeader("Content-Type") === "application/pdf", "Company receives application/pdf");
    assertCondition($res->isFile() === true, "Response is marked as file response");
    assertCondition(file_exists((string)$res->getFilePath()), "Response file path points to existing file");
    $fileContent = file_get_contents($res->getFilePath());
    assertCondition(str_contains($fileContent, "PDF-CONTENT-STUDENT-1-VERSION-1"), "Company receives exact student PDF");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 8: Other student cannot retrieve application CV -> 403 (Case 7)
    // =========================================================================
    echo "Running Test 8: Other student forbidden -> 403 (Case 7)... ";
    $res = $makeRequest("GET", "/applications/{$app1Id}/cv", [], $student2["token"]);
    assertCondition($res->getStatusCode() === 403, "Other student must receive 403 Forbidden. Got: {$res->getStatusCode()}");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 9: Other company cannot retrieve application CV -> 403 (Case 8)
    // =========================================================================
    echo "Running Test 9: Other company forbidden -> 403 (Case 8)... ";
    $res = $makeRequest("GET", "/applications/{$app1Id}/cv", [], $comp2User["token"]);
    assertCondition($res->getStatusCode() === 403, "Other company must receive 403 Forbidden. Got: {$res->getStatusCode()}");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 10: Admin cannot retrieve application CV -> 403 (Case 9)
    // =========================================================================
    echo "Running Test 10: Admin forbidden -> 403 (Case 9)... ";
    $res = $makeRequest("GET", "/applications/{$app1Id}/cv", [], $adminUser["token"]);
    assertCondition($res->getStatusCode() === 403, "Admin must receive 403 Forbidden. Got: {$res->getStatusCode()}");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 11: Unauthenticated guest cannot retrieve application CV -> 401 (Case 10)
    // =========================================================================
    echo "Running Test 11: Guest unauthorized -> 401 (Case 10)... ";
    $res = $makeRequest("GET", "/applications/{$app1Id}/cv", [], null);
    assertCondition($res->getStatusCode() === 401, "Guest must receive 401 Unauthorized. Got: {$res->getStatusCode()}");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 12: Non-existent application ID -> 404 (Case 11)
    // =========================================================================
    echo "Running Test 12: Non-existent application ID -> 404 (Case 11)... ";
    $res = $makeRequest("GET", "/applications/non_existent_app_999/cv", [], $student1["token"]);
    assertCondition($res->getStatusCode() === 404, "Non-existent application must return 404. Got: {$res->getStatusCode()}");
    $resComp = $makeRequest("GET", "/applications/non_existent_app_999/cv", [], $comp1User["token"]);
    assertCondition($resComp->getStatusCode() === 404, "Non-existent application for company must return 404. Got: {$resComp->getStatusCode()}");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 13: Physical file deleted from storage -> 404 (Case 12)
    // =========================================================================
    echo "Running Test 13: Physical file missing from storage -> 404 (Case 12)... ";
    // Insert dummy application pointing to non-existent file on a separate job
    $ghostJob = $createJob("ghost_job", $comp1);
    $ghostAppId = "{$fixturePrefix}ghost_app";
    $stmt = $db->prepare(
        "INSERT INTO applications (id, job_id, developer_id, status, cv_storage_path, cv_original_name, cv_mime_type, applied_at)
         VALUES (?, ?, ?, 'pending', 'ghost_file.pdf', 'ghost.pdf', 'application/pdf', NOW())"
    );
    $stmt->execute([$ghostAppId, $ghostJob["id"], $student1["id"]]);

    $res = $makeRequest("GET", "/applications/{$ghostAppId}/cv", [], $student1["token"]);
    assertCondition($res->getStatusCode() === 404, "Application with missing disk file must return 404. Got: {$res->getStatusCode()}");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 14: Student replaces active profile CV after applying (Case 13)
    // =========================================================================
    echo "Running Test 14: Student replaces active CV after apply (Case 13)... ";
    $tmpPdf2 = createTestTempPdf("PDF-CONTENT-STUDENT-1-VERSION-2");
    $cvService->uploadActiveCv($student1, [
        "name"     => "my_cv_v2.pdf",
        "tmp_name" => $tmpPdf2,
        "size"     => filesize($tmpPdf2),
        "error"    => UPLOAD_ERR_OK,
    ]);

    // Verify student active profile now points to new file
    $std1ProfileV2 = $profileRepo->findByUserId($student1["id"]);
    $activeCvPathV2 = $std1ProfileV2["cv_storage_path"];
    assertCondition($activeCvPathV2 !== $activeCvPathV1, "Active CV path has updated in profile");
    assertCondition($storageService->fileExists($activeCvPathV2), "New CV file exists on disk");
    assertCondition($storageService->fileExists($activeCvPathV1), "Old CV file STILL exists on disk");

    // Verify Application 1 snapshot still points to V1 and can be retrieved
    $res = $makeRequest("GET", "/applications/{$app1Id}/cv", [], $comp1User["token"]);
    assertCondition($res->getStatusCode() === 200, "Company can still retrieve Application 1 CV. Got: {$res->getStatusCode()}");
    assertCondition($res->isFile() === true, "Response is marked as file response");
    assertCondition(file_exists((string)$res->getFilePath()), "Response file path points to existing file");
    $streamContent = file_get_contents($res->getFilePath());
    assertCondition(str_contains($streamContent, "PDF-CONTENT-STUDENT-1-VERSION-1"), "Application 1 CV still contains Version 1 content");
    assertCondition(!str_contains($streamContent, "PDF-CONTENT-STUDENT-1-VERSION-2"), "Application 1 CV does not contain Version 2 content");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 15: Student deletes active CV after applying -> snapshot file preserved (Case 14)
    // =========================================================================
    echo "Running Test 15: Student deletes active profile CV after apply (Case 14)... ";
    // Apply to Job 2 (as Student 1) so V2 is snapshotted to Job 2 application
    $resJob2 = $makeRequest("POST", "/jobs/{$job2['id']}/applications", [
        "preferred_shift" => "afternoon"
    ], $student1["token"]);
    assertCondition($resJob2->getStatusCode() === 201, "Student 1 applied to Job 2 with CV V2");
    $app2Id = $resJob2->getPayload()["data"]["id"];

    // Now delete active CV from profile
    $cvService->deleteActiveCv($student1);

    // Active CV metadata should be cleared in student_profiles
    $std1ProfileCleared = $profileRepo->findByUserId($student1["id"]);
    assertCondition(empty($std1ProfileCleared["cv_storage_path"]), "Active profile cv_storage_path is cleared");

    // But BOTH physical files (V1 referenced by app1, V2 referenced by app2) MUST be preserved on disk!
    assertCondition($storageService->fileExists($activeCvPathV1), "V1 file is preserved because App 1 references it");
    assertCondition($storageService->fileExists($activeCvPathV2), "V2 file is preserved because App 2 references it");

    // Both applications remain accessible
    $resApp1 = $makeRequest("GET", "/applications/{$app1Id}/cv", [], $student1["token"]);
    assertCondition($resApp1->getStatusCode() === 200, "App 1 CV still downloadable after profile CV deletion");

    $resApp2 = $makeRequest("GET", "/applications/{$app2Id}/cv", [], $comp2User["token"]);
    assertCondition($resApp2->getStatusCode() === 200, "App 2 CV still downloadable by Company 2 after profile CV deletion");
    assertCondition($resApp2->isFile() === true, "Response is marked as file response");
    assertCondition(file_exists((string)$resApp2->getFilePath()), "Response file path points to existing file");
    $streamContent2 = file_get_contents($resApp2->getFilePath());
    assertCondition(str_contains($streamContent2, "PDF-CONTENT-STUDENT-1-VERSION-2"), "App 2 CV contains Version 2 content");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 16: Student with both legacy profile cv_url AND valid private uploaded CV
    // =========================================================================
    echo "Running Test 16: Student with both legacy cv_url and private active CV... ";
    // Seed Student 2 with both a legacy cv_url and a valid uploaded private CV
    $tmpPdfStd2 = createTestTempPdf("PDF-CONTENT-STUDENT-2-PRIVATE");
    $cvService->uploadActiveCv($student2, [
        "name"     => "student2_cv.pdf",
        "tmp_name" => $tmpPdfStd2,
        "size"     => filesize($tmpPdfStd2),
        "error"    => UPLOAD_ERR_OK,
    ]);

    // Manually set a legacy cv_url on Student 2's profile
    $legacyUrl = "https://external-portfolio.example.com/legacy_student2_resume.pdf";
    $stmt = $db->prepare("UPDATE student_profiles SET cv_url = ? WHERE user_id = ?");
    $stmt->execute([$legacyUrl, $student2["id"]]);

    // Verify student 2 profile has BOTH legacy cv_url and active private CV
    $std2Profile = $profileRepo->findByUserId($student2["id"]);
    assertCondition($std2Profile["cv_url"] === $legacyUrl, "Student 2 has legacy cv_url in profile");
    assertCondition(!empty($std2Profile["cv_storage_path"]), "Student 2 has private cv_storage_path in profile");
    $std2StoragePath = $std2Profile["cv_storage_path"];

    // Student 2 applies to Job 1 (also attempting to pass client resume / cv_url_snapshot)
    $resStd2App = $makeRequest("POST", "/jobs/{$job1['id']}/applications", [
        "preferred_shift" => "flexible",
        "cover_letter"    => "Em chào công ty 1",
        "resume"          => "https://attacker.com/malicious.pdf",
        "cv_url_snapshot" => "https://attacker.com/malicious2.pdf",
    ], $student2["token"]);

    assertCondition($resStd2App->getStatusCode() === 201, "Application succeeds with private snapshot. Got: {$resStd2App->getStatusCode()}");
    $std2AppData = $resStd2App->getPayload()["data"] ?? [];
    $std2AppId = $std2AppData["id"] ?? "";
    assertCondition(!empty($std2AppId), "Application ID returned for student 2");

    // Assert API response has private snapshot and NULL resume / cv_url_snapshot
    assertCondition($std2AppData["has_cv_snapshot"] === true, "Response has has_cv_snapshot = true");
    assertCondition($std2AppData["cv_file_name"] === "student2_cv.pdf", "Response cv_file_name matches private upload");
    assertCondition($std2AppData["resume"] === null, "Response resume must be NULL (no external CV URL)");
    assertCondition($std2AppData["cv_url_snapshot"] === null, "Response cv_url_snapshot must be NULL (no external CV URL)");

    // Assert Database record has private snapshot and NULL resume
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$std2AppId]);
    $dbStd2App = $stmt->fetch(PDO::FETCH_ASSOC);
    assertCondition($dbStd2App !== false, "Database record exists for student 2 application");
    assertCondition($dbStd2App["cv_storage_path"] === $std2StoragePath, "Database cv_storage_path matches private upload");
    assertCondition($dbStd2App["cv_original_name"] === "student2_cv.pdf", "Database cv_original_name matches private upload");
    assertCondition($dbStd2App["resume"] === null, "Database resume column MUST be NULL for new applications");

    // Assert private snapshot can be retrieved via protected endpoint
    $resDownload = $makeRequest("GET", "/applications/{$std2AppId}/cv", [], $student2["token"]);
    assertCondition($resDownload->getStatusCode() === 200, "Student 2 can retrieve private snapshot. Got: {$resDownload->getStatusCode()}");
    assertCondition($resDownload->isFile() === true, "Response is file response");
    assertCondition(file_exists((string)$resDownload->getFilePath()), "Download file path exists");
    $downloadContent = file_get_contents($resDownload->getFilePath());
    assertCondition(str_contains($downloadContent, "PDF-CONTENT-STUDENT-2-PRIVATE"), "Downloaded content matches Student 2 private PDF");

    // Authorized Company 1 can also retrieve it
    $resCompDownload = $makeRequest("GET", "/applications/{$std2AppId}/cv", [], $comp1User["token"]);
    assertCondition($resCompDownload->getStatusCode() === 200, "Company 1 can retrieve student 2 private snapshot");
    assertCondition($resCompDownload->isFile() === true, "Company response is file response");
    $compDownloadContent = file_get_contents($resCompDownload->getFilePath());
    assertCondition(str_contains($compDownloadContent, "PDF-CONTENT-STUDENT-2-PRIVATE"), "Company 1 receives student 2 private PDF");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 17: Legacy application records preserved without destructive modification
    // =========================================================================
    echo "Running Test 17: Legacy application records preserved... ";
    $legacyJob = $createJob("legacy_job", $comp1);
    $legacyAppId = "{$fixturePrefix}legacy_archive_app";
    $legacyResumeUrl = "https://legacy-archive.example.com/archived_cv.pdf";
    $stmt = $db->prepare(
        "INSERT INTO applications (id, job_id, developer_id, status, resume, cv_storage_path, cv_original_name, applied_at)
         VALUES (?, ?, ?, 'pending', ?, NULL, NULL, NOW())"
    );
    $stmt->execute([$legacyAppId, $legacyJob["id"], $student1["id"], $legacyResumeUrl]);

    // Student 1 views detail of legacy application
    $resLegacyDetail = $makeRequest("GET", "/applications/{$legacyAppId}", [], $student1["token"]);
    assertCondition($resLegacyDetail->getStatusCode() === 200, "Can view legacy application detail");
    $legacyData = $resLegacyDetail->getPayload()["data"] ?? [];
    assertCondition($legacyData["resume"] === $legacyResumeUrl, "Legacy application preserves resume URL");
    assertCondition($legacyData["cv_url_snapshot"] === $legacyResumeUrl, "Legacy application preserves cv_url_snapshot");
    assertCondition($legacyData["has_cv_snapshot"] === false, "Legacy application has_cv_snapshot is false");

    // Attempting to download CV for legacy application without snapshot returns 404
    $resLegacyDownload = $makeRequest("GET", "/applications/{$legacyAppId}/cv", [], $student1["token"]);
    assertCondition($resLegacyDownload->getStatusCode() === 404, "Legacy application without private snapshot returns 404 on /cv endpoint");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // TEST 18: Duplicate Application Rule (One-time application)
    // =========================================================================
    echo "Running Test 18: Duplicate application prevention (409 Conflict)... ";
    // Restore a dummy active CV so student can attempt apply
    $p = new Profile($student1["id"]);
    $p->setCvStoragePath($activeCvPathV1);
    $p->setCvOriginalName("my_cv_v1.pdf");
    $p->setCvFileSize(1234);
    $p->setCvMimeType("application/pdf");
    $profileRepo->upsert($p);

    $dupRes = $makeRequest("POST", "/jobs/{$job1['id']}/applications", [
        "preferred_shift" => "evening"
    ], $student1["token"]);
    assertCondition($dupRes->getStatusCode() === 409, "Duplicate apply must return 409 Conflict. Got: {$dupRes->getStatusCode()}");
    echo "[PASS]\n";
    $testsPassed++;

    // =========================================================================
    // SUMMARY
    // =========================================================================
    echo "=================================================================\n";
    echo " ALL {$testsPassed} CV-P0-02 REGRESSION TESTS PASSED SUCCESSFULLY! \n";
    echo "=================================================================\n";

} finally {
    $cleanup();
}