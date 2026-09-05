<?php

/**
 * ============================================================================
 * DEDICATED REGRESSION TEST SUITE: GOOGLE-AUTH-P0-01
 * OAuth identity schema, constraints, and atomic repository transactions.
 * ============================================================================
 *
 * Requirements:
 * - Fail-fast unless running in explicit testing environment (APP_ENV=testing).
 * - Fail-fast unless running against dedicated test database (containing 'test').
 * - Never use development/shared database.
 * - Cleanup only test fixtures owned by a unique prefix inside finally.
 *
 * Test coverage:
 * a. duplicate (provider, provider_subject) is rejected
 * b. one user cannot have two Google identities
 * c. Company Google-origin user creates exactly one pending company skeleton
 * d. failure while inserting identity/company rolls back user and prevents orphan data
 * e. admin, developer, employer, and arbitrary roles are rejected
 * f. non-google provider is rejected
 * g. mixed-case email is normalized and still collides with an existing local account
 * h. OAuth token/secret fields are not persisted
 */

define("BASE_PATH", __DIR__);

require_once BASE_PATH . "/vendor/autoload.php";

// 1. Fail-fast environment check: Must have .env.testing
$envTestingFile = __DIR__ . "/.env.testing";
if (!file_exists($envTestingFile)) {
    fwrite(STDERR, "[FAIL-FAST] Lỗi nghiêm trọng: Không tìm thấy file '.env.testing'. Vui lòng cấu hình môi trường test biệt lập." . PHP_EOL);
    exit(1);
}

$dotenv = Dotenv\Dotenv::createMutable(__DIR__, ".env.testing");
$dotenv->load();

use JobMarket\Domain\Authentication\OAuthIdentity;
use JobMarket\Infrastructure\OAuthIdentityRepository;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;

$appEnv = $_ENV["APP_ENV"] ?? getenv("APP_ENV") ?: "";
$config = Config::env();

// 2. Fail-fast: Must be testing environment
if ($appEnv !== "testing") {
    fwrite(STDERR, "[CHẶN TOÀN BỘ] Environment hiện tại là '{$appEnv}'. Test chỉ được phép chạy trên environment 'testing'!" . PHP_EOL);
    exit(1);
}

// 3. Fail-fast: Must be dedicated test database
if (!isset($config["dbname"]) || $config["dbname"] !== "jobmarket_test") {
    fwrite(STDERR, "[CHẶN TOÀN BỘ] Database '{$config['dbname']}' không phải 'jobmarket_test'! Nguy cơ ảnh hưởng dữ liệu." . PHP_EOL);
    exit(1);
}

try {
    $db = new PDO(
        "mysql:dbname={$config['dbname']};host={$config['host']};port=" . ($config["port"] ?? 3306),
        $config["user"],
        $config["password"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "[FAIL-FAST] Không thể kết nối cơ sở dữ liệu test '{$config['dbname']}': " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Check if oauth_identities table exists in test DB
$tableCheck = $db->query("SHOW TABLES LIKE 'oauth_identities'")->fetch();
if (!$tableCheck) {
    fwrite(STDERR, "[FAIL-FAST] Bảng 'oauth_identities' chưa được migrate trên database test '{$config['dbname']}'." . PHP_EOL);
    exit(1);
}

$repo = new OAuthIdentityRepository($db);

// Unique test prefix to ensure zero collision with any other data and clean fixture scoping
$testPrefix = "test_p0_01_" . bin2hex(random_bytes(6)) . "_";

function cleanupFixtures(PDO $db, string $prefix): void
{
    // Clean any temporary triggers matching our unique test prefix
    $triggerName = "trig_" . $prefix . "fail_after_writes";
    try {
        $db->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
    } catch (Throwable $_) {}

    // Clean only records matching our unique test prefix
    $stmtUsers = $db->prepare("SELECT id FROM users WHERE email LIKE ?");
    $stmtUsers->execute([$prefix . "%"]);
    $userIds = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($userIds)) {
        $inClause = implode(",", array_fill(0, count($userIds), "?"));
        
        $delOauth = $db->prepare("DELETE FROM oauth_identities WHERE user_id IN ($inClause)");
        $delOauth->execute($userIds);

        $delComp = $db->prepare("DELETE FROM companies WHERE user_id IN ($inClause)");
        $delComp->execute($userIds);

        $delUsers = $db->prepare("DELETE FROM users WHERE id IN ($inClause)");
        $delUsers->execute($userIds);
    }

    // Also clean any identities with prefix in provider_subject
    $delIdSub = $db->prepare("DELETE FROM oauth_identities WHERE provider_subject LIKE ?");
    $delIdSub->execute([$prefix . "%"]);
}

register_shutdown_function(function() use ($db, $testPrefix) {
    cleanupFixtures($db, $testPrefix);
});

$passed = 0;
$total = 0;

function runTest(string $name, callable $fn): void
{
    global $passed, $total;
    $total++;
    try {
        $fn();
        echo "  [PASS] {$name}" . PHP_EOL;
        $passed++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$name}: " . $e->getMessage() . PHP_EOL;
        echo "         At: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    }
}

echo "=================================================================" . PHP_EOL;
echo "   CHẠY BỘ KIỂM THỬ ĐỘC LẬP / HỒI QUY: GOOGLE-AUTH-P0-01         " . PHP_EOL;
echo "   (MÔI TRƯỜNG: {$appEnv} | DATABASE: {$config['dbname']})        " . PHP_EOL;
echo "   PREFIX FIXTURE: {$testPrefix}                                  " . PHP_EOL;
echo "=================================================================" . PHP_EOL . PHP_EOL;

try {
    // -------------------------------------------------------------------------
    // TEST A: Duplicate (provider, provider_subject) is rejected
    // -------------------------------------------------------------------------
    runTest("a. duplicate (provider, provider_subject) is rejected", function() use ($repo, $db, $testPrefix) {
        $email1 = $testPrefix . "user_a1@example.com";
        $email2 = $testPrefix . "user_a2@example.com";
        $subA = $testPrefix . "sub_duplicate_check";

        // Create first user
        $res1 = $repo->createOAuthUserWithIdentity(
            ["name" => "User A1", "email" => $email1, "role" => "student"],
            ["provider" => "google", "provider_subject" => $subA, "email_at_link" => $email1]
        );
        if (empty($res1["user"]["id"])) {
            throw new Exception("Không thể tạo User A1 ban đầu.");
        }

        // Attempt second user with exact same provider and provider_subject
        $threw = false;
        try {
            $repo->createOAuthUserWithIdentity(
                ["name" => "User A2", "email" => $email2, "role" => "student"],
                ["provider" => "google", "provider_subject" => $subA, "email_at_link" => $email2]
            );
        } catch (ValidationException | PDOException $e) {
            $threw = true;
        }

        if (!$threw) {
            throw new Exception("Hệ thống phải từ chối khi provider_subject trùng lặp.");
        }

        // Verify User A2 was NOT inserted into users table
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email2]);
        if ($stmt->fetch()) {
            throw new Exception("User A2 không được tạo khi identity sub bị trùng lặp.");
        }
    });

    // -------------------------------------------------------------------------
    // TEST B: One user cannot have two Google identities
    // -------------------------------------------------------------------------
    runTest("b. one user cannot have two Google identities", function() use ($repo, $db, $testPrefix) {
        $emailB = $testPrefix . "user_b@example.com";
        $subB1 = $testPrefix . "sub_b1";
        $subB2 = $testPrefix . "sub_b2";

        $res = $repo->createOAuthUserWithIdentity(
            ["name" => "User B", "email" => $emailB, "role" => "student"],
            ["provider" => "google", "provider_subject" => $subB1, "email_at_link" => $emailB]
        );
        $userId = $res["user"]["id"];

        // Attempt to insert a second Google identity for the same user_id
        $threw = false;
        try {
            $secondIdentity = new OAuthIdentity($userId, "google", $subB2, $emailB);
            $repo->createIdentity($secondIdentity);
        } catch (PDOException $e) {
            // SQLSTATE 23000 Duplicate entry for key 'unique_user_provider'
            $threw = true;
        }

        if (!$threw) {
            throw new Exception("Ràng buộc UNIQUE(user_id, provider) phải chặn việc thêm identity Google thứ 2 cho cùng 1 user.");
        }
    });

    // -------------------------------------------------------------------------
    // TEST C: Company Google-origin user creates exactly one pending company skeleton
    // -------------------------------------------------------------------------
    runTest("c. Company Google-origin user creates exactly one pending company skeleton", function() use ($repo, $db, $testPrefix) {
        $emailC = $testPrefix . "company_c@example.com";
        $subC = $testPrefix . "sub_c";
        $companyName = "Doanh Nghiệp Test C";

        $res = $repo->createOAuthUserWithIdentity(
            [
                "name" => "Đại Diện C",
                "email" => $emailC,
                "role" => "company",
                "company_name" => $companyName
            ],
            [
                "provider" => "google",
                "provider_subject" => $subC,
                "email_at_link" => $emailC
            ]
        );

        $userId = $res["user"]["id"];
        if ($res["user"]["role"] !== "company") {
            throw new Exception("User role phải là 'company'.");
        }

        // Verify company record in database
        $stmt = $db->prepare("SELECT * FROM companies WHERE user_id = ?");
        $stmt->execute([$userId]);
        $companies = $stmt->fetchAll();

        if (count($companies) !== 1) {
            throw new Exception("Kỳ vọng đúng 1 bản ghi company skeleton, tìm thấy: " . count($companies));
        }

        $comp = $companies[0];
        if ($comp["verification_status"] !== "pending") {
            throw new Exception("verification_status của company mới phải là 'pending', thực tế: " . $comp["verification_status"]);
        }
        if ($comp["name"] !== $companyName) {
            throw new Exception("Tên company không khớp.");
        }
    });

    // -------------------------------------------------------------------------
    // TEST D: Failure while inserting identity/company rolls back user and prevents orphan data
    // -------------------------------------------------------------------------
    runTest("d. failure while inserting identity/company rolls back user and prevents orphan data", function() use ($repo, $db, $testPrefix) {
        $triggerName = "trig_" . $testPrefix . "fail_after_writes";
        $triggerFailSub = $testPrefix . "sub_trigger_write_fail";
        $emailDFail = $testPrefix . "trigger_fail_comp@example.com";
        $companyNameFail = "Company Trigger Fail " . $testPrefix;

        // In the dedicated test DB only, create a temporary BEFORE INSERT trigger on oauth_identities.
        // This triggers a failure AFTER:
        // 1. user insert into `users`, and
        // 2. company skeleton insert into `companies`,
        // but BEFORE transaction commit, proving true atomic rollback after partial writes.
        $db->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
        $db->exec(
            "CREATE TRIGGER `{$triggerName}` BEFORE INSERT ON `oauth_identities`
             FOR EACH ROW
             BEGIN
                 IF NEW.provider_subject = '{$triggerFailSub}' THEN
                     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulated write failure on oauth_identities after user and company insert';
                 END IF;
             END;"
        );

        $threw = false;
        $caughtMessage = "";
        try {
            $repo->createOAuthUserWithIdentity(
                [
                    "name" => "Company Rep Trigger Fail",
                    "email" => $emailDFail,
                    "role" => "company",
                    "company_name" => $companyNameFail
                ],
                [
                    "provider" => "google",
                    "provider_subject" => $triggerFailSub,
                    "email_at_link" => $emailDFail
                ]
            );
        } catch (Throwable $e) {
            $threw = true;
            $caughtMessage = $e->getMessage();
        } finally {
            // Always drop the temporary trigger in finally, including on assertion failure
            $db->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
        }

        if (!$threw) {
            throw new Exception("Hệ thống phải ném ngoại lệ khi trigger kích hoạt lỗi ghi.");
        }

        if (strpos($caughtMessage, "Simulated write failure") === false) {
            throw new Exception("Ngoại lệ bắt được không phải từ trigger mô phỏng lỗi: {$caughtMessage}");
        }

        // Verify that the user insert was rolled back (no orphan user)
        $stmtU = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmtU->execute([$emailDFail]);
        if ($stmtU->fetch()) {
            throw new Exception("Phát hiện ORPHAN USER trong users table sau rollback!");
        }

        // Verify that the company skeleton insert was rolled back (no orphan company)
        $stmtC = $db->prepare("SELECT id FROM companies WHERE name = ?");
        $stmtC->execute([$companyNameFail]);
        if ($stmtC->fetch()) {
            throw new Exception("Phát hiện ORPHAN COMPANY trong companies table sau rollback!");
        }

        // Verify that no OAuth identity record remains
        $stmtI = $db->prepare("SELECT id FROM oauth_identities WHERE provider_subject = ?");
        $stmtI->execute([$triggerFailSub]);
        if ($stmtI->fetch()) {
            throw new Exception("Phát hiện ORPHAN OAUTH IDENTITY trong oauth_identities table sau rollback!");
        }
    });

    // -------------------------------------------------------------------------
    // TEST E: Admin, developer, employer, and arbitrary roles are rejected
    // -------------------------------------------------------------------------
    runTest("e. admin, developer, employer, and arbitrary roles are rejected", function() use ($repo, $db, $testPrefix) {
        $disallowedRoles = ["admin", "developer", "employer", "root", "guest", "superadmin", ""];

        foreach ($disallowedRoles as $role) {
            $emailE = $testPrefix . "role_{$role}@example.com";
            $threw = false;
            try {
                $repo->createOAuthUserWithIdentity(
                    ["name" => "Bad Role {$role}", "email" => $emailE, "role" => $role],
                    ["provider" => "google", "provider_subject" => $testPrefix . "sub_role_{$role}"]
                );
            } catch (InvalidArgumentException $e) {
                $threw = true;
            }

            if (!$threw) {
                throw new Exception("Vai trò '{$role}' phải bị ném InvalidArgumentException và từ chối.");
            }

            // Verify not inserted
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$emailE]);
            if ($stmt->fetch()) {
                throw new Exception("User với vai trò '{$role}' không được phép ghi vào CSDL.");
            }
        }
    });

    // -------------------------------------------------------------------------
    // TEST F: Non-google provider is rejected
    // -------------------------------------------------------------------------
    runTest("f. non-google provider is rejected", function() use ($repo, $db, $testPrefix) {
        $disallowedProviders = ["facebook", "github", "apple", "microsoft", "twitter", ""];

        foreach ($disallowedProviders as $prov) {
            $emailF = $testPrefix . "prov_{$prov}@example.com";
            $threw = false;
            try {
                $repo->createOAuthUserWithIdentity(
                    ["name" => "Bad Provider", "email" => $emailF, "role" => "student"],
                    ["provider" => $prov, "provider_subject" => $testPrefix . "sub_prov_{$prov}"]
                );
            } catch (InvalidArgumentException $e) {
                $threw = true;
            }

            if (!$threw) {
                throw new Exception("Provider '{$prov}' không phải 'google' phải bị từ chối.");
            }

            // Verify not inserted
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$emailF]);
            if ($stmt->fetch()) {
                throw new Exception("Provider không hợp lệ không được tạo user.");
            }
        }
    });

    // -------------------------------------------------------------------------
    // TEST G: Mixed-case email is normalized and still collides with an existing local account
    // -------------------------------------------------------------------------
    runTest("g. mixed-case email is normalized and still collides with an existing local account", function() use ($repo, $db, $testPrefix) {
        $baseEmail = $testPrefix . "collision_test@example.com";
        $mixedCaseInput = strtoupper($testPrefix) . "CoLLiSioN_TeSt@ExAmPLe.CoM";
        $localUserId = "usr-" . uniqid();

        // 1. Seed a local account with lowercase email
        $stmtInsert = $db->prepare(
            "INSERT INTO users (id, name, email, password, role, status) VALUES (?, 'Local User Seed', ?, 'dummy_hash', 'student', 'active')"
        );
        $stmtInsert->execute([$localUserId, $baseEmail]);

        // 2. Attempt OAuth creation with mixed-case email -> must collide and throw ValidationException
        $threw = false;
        try {
            $repo->createOAuthUserWithIdentity(
                ["name" => "OAuth Collision Attacker", "email" => $mixedCaseInput, "role" => "student"],
                ["provider" => "google", "provider_subject" => $testPrefix . "sub_collision_g", "email_at_link" => $mixedCaseInput]
            );
        } catch (ValidationException $e) {
            $threw = true;
            $errors = $e->getErrors();
            if (empty($errors["email"])) {
                throw new Exception("ValidationException phải trả về lỗi cho trường email.");
            }
        }

        if (!$threw) {
            throw new Exception("Email chữ hoa/thường phải được chuẩn hóa và phát hiện va chạm với local account.");
        }

        // 3. Test findLocalUserByEmail normalizes its argument
        $found = $repo->findLocalUserByEmail($mixedCaseInput);
        if (!$found || $found["email"] !== $baseEmail) {
            throw new Exception("findLocalUserByEmail() phải tìm thấy user khi truyền mixed-case email.");
        }

        // 4. Test fresh creation normalizes email and email_at_link in DB
        $freshMixedEmail = $testPrefix . "Fresh.Mixed.Case@Example.Com";
        $freshNormalized = strtolower(trim($freshMixedEmail));
        $freshSub = $testPrefix . "sub_fresh_mixed";

        $freshRes = $repo->createOAuthUserWithIdentity(
            ["name" => "Fresh Mixed User", "email" => $freshMixedEmail, "role" => "student"],
            ["provider" => "google", "provider_subject" => $freshSub, "email_at_link" => $freshMixedEmail]
        );

        $freshUserId = $freshRes["user"]["id"];

        // Verify users.email is normalized
        $stmtUser = $db->prepare("SELECT email FROM users WHERE id = ?");
        $stmtUser->execute([$freshUserId]);
        $storedUserEmail = $stmtUser->fetchColumn();
        if ($storedUserEmail !== $freshNormalized) {
            throw new Exception("users.email trong CSDL phải là chữ thường: '{$freshNormalized}', thực tế: '{$storedUserEmail}'");
        }

        // Verify oauth_identities.email_at_link is normalized
        $stmtId = $db->prepare("SELECT email_at_link FROM oauth_identities WHERE user_id = ?");
        $stmtId->execute([$freshUserId]);
        $storedIdEmail = $stmtId->fetchColumn();
        if ($storedIdEmail !== $freshNormalized) {
            throw new Exception("oauth_identities.email_at_link phải là chữ thường: '{$freshNormalized}', thực tế: '{$storedIdEmail}'");
        }
    });

    // -------------------------------------------------------------------------
    // TEST H: OAuth token/secret fields are not persisted
    // -------------------------------------------------------------------------
    runTest("h. OAuth token/secret fields are not persisted", function() use ($db) {
        $stmt = $db->query("DESCRIBE oauth_identities");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $forbiddenFields = [
            "access_token",
            "refresh_token",
            "id_token",
            "raw_token",
            "token",
            "client_secret",
            "secret",
            "code"
        ];

        foreach ($forbiddenFields as $f) {
            if (in_array($f, $columns, true)) {
                throw new Exception("Phát hiện cột cấm trong bảng oauth_identities: '{$f}'!");
            }
        }

        // Explicitly verify allowed columns only
        $allowed = ["id", "user_id", "provider", "provider_subject", "email_at_link", "created_at", "updated_at"];
        foreach ($columns as $c) {
            if (!in_array($c, $allowed, true)) {
                throw new Exception("Cột không xác định trong schema oauth_identities: '{$c}'");
            }
        }
    });

} finally {
    cleanupFixtures($db, $testPrefix);
}

echo PHP_EOL . "=================================================================" . PHP_EOL;
if ($passed === $total && $total === 8) {
    echo "   KẾT QUẢ: {$passed}/{$total} BÀI KIỂM THỬ ĐẠT THÀNH CÔNG (100% PASS)       " . PHP_EOL;
    echo "   TẤT CẢ TIÊU CHÍ GOOGLE-AUTH-P0-01 ĐẠT CHUẨN!                  " . PHP_EOL;
    echo "=================================================================" . PHP_EOL;
    exit(0);
} else {
    echo "   KẾT QUẢ: {$passed}/{$total} BÀI KIỂM THỬ THÀNH CÔNG. CÓ BÀI THẤT BẠI!   " . PHP_EOL;
    echo "=================================================================" . PHP_EOL;
    exit(1);
}