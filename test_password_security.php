<?php

define("BASE_PATH", __DIR__);
require __DIR__ . "/vendor/autoload.php";

use JobMarket\Domain\AccountSecurityService;
use JobMarket\Domain\MailService;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
$config = Config::env();
$db = new PDO(
    "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4",
    $config["user"],
    $config["password"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$mail = new class extends MailService {
    public ?string $lastCode = null;
    public function sendPasswordChangeCode(array $recipient, string $code, int $validMinutes): array
    {
        $this->lastCode = $code;
        return ["status" => "sent", "error" => null, "preview_path" => null];
    }
    public function sendPasswordChangedNotice(array $recipient): array
    {
        return ["status" => "sent", "error" => null, "preview_path" => null];
    }
};

$assert = function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$expectValidation = function (callable $operation, string $message): void {
    try {
        $operation();
    } catch (ValidationException) {
        return;
    }
    throw new RuntimeException($message);
};

$localId = "pwd-local-" . bin2hex(random_bytes(6));
$googleId = "pwd-google-" . bin2hex(random_bytes(6));
try {
    $insertUser = $db->prepare(
        "INSERT INTO users (id, name, email, password, password_set_at, role, status, token, token_expires_at)
         VALUES (?, ?, ?, ?, ?, 'student', 'active', 'test-token', DATE_ADD(NOW(), INTERVAL 1 HOUR))"
    );
    $oldPassword = "OldPassword123";
    $insertUser->execute([$localId, "Local Test", $localId . "@example.test", password_hash($oldPassword, PASSWORD_DEFAULT), date("Y-m-d H:i:s")]);
    $insertUser->execute([$googleId, "Google Test", $googleId . "@example.test", password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), null]);
    $identity = $db->prepare(
        "INSERT INTO oauth_identities (id, user_id, provider, provider_subject, email_at_link) VALUES (?, ?, 'google', ?, ?)"
    );
    $identity->execute(["oid-" . bin2hex(random_bytes(8)), $googleId, "sub-" . bin2hex(random_bytes(8)), $googleId . "@example.test"]);

    $service = new AccountSecurityService($mail, $db);
    $localSession = ["id" => $localId, "email" => $localId . "@example.test", "role" => "student"];
    $localStatus = $service->passwordStatus($localSession);
    $assert($localStatus["mode"] === "change" && $localStatus["requires_email_code"] === true, "Local account status is incorrect.");

    $challenge = $service->requestChangeCode($localSession, ["current_password" => $oldPassword]);
    $assert($mail->lastCode !== null && strlen($mail->lastCode) === 6, "Email verification code was not generated.");
    $storedCodeHash = $db->query(
        "SELECT code_hash FROM password_change_challenges WHERE id = " . $db->quote($challenge["challenge_id"])
    )->fetchColumn();
    $assert($storedCodeHash !== $mail->lastCode && password_verify($mail->lastCode, $storedCodeHash), "Verification code was not stored as a secure hash.");
    $expectValidation(
        fn() => $service->verifyChangeCode($localSession, ["challenge_id" => $challenge["challenge_id"], "code" => "000000"]),
        "An incorrect verification code was accepted."
    );
    $verified = $service->verifyChangeCode($localSession, ["challenge_id" => $challenge["challenge_id"], "code" => $mail->lastCode]);
    $assert($verified["verified"] === true, "Email verification code was not accepted.");
    $newPassword = "NewPassword456";
    $service->updatePassword($localSession, [
        "challenge_id" => $challenge["challenge_id"],
        "password" => $newPassword,
        "password_confirmation" => $newPassword,
    ]);
    $localRow = $db->query("SELECT password, token FROM users WHERE id = " . $db->quote($localId))->fetch(PDO::FETCH_ASSOC);
    $assert(password_verify($newPassword, $localRow["password"]), "Local password was not updated.");
    $assert($localRow["token"] === "revoked", "Old session was not revoked.");
    $expectValidation(
        fn() => $service->updatePassword($localSession, [
            "challenge_id" => $challenge["challenge_id"],
            "password" => "AnotherPassword890",
            "password_confirmation" => "AnotherPassword890",
        ]),
        "A consumed email challenge was reused."
    );

    $googleSession = ["id" => $googleId, "email" => $googleId . "@example.test", "role" => "student"];
    $googleStatus = $service->passwordStatus($googleSession);
    $assert($googleStatus["mode"] === "set_first_password" && $googleStatus["requires_email_code"] === false, "Google-only status is incorrect.");
    $googlePassword = "GooglePassword789";
    $service->updatePassword($googleSession, ["password" => $googlePassword, "password_confirmation" => $googlePassword]);
    $googleRow = $db->query("SELECT password, password_set_at FROM users WHERE id = " . $db->quote($googleId))->fetch(PDO::FETCH_ASSOC);
    $assert(password_verify($googlePassword, $googleRow["password"]) && $googleRow["password_set_at"] !== null, "Google first password was not set.");
    $googleStatusAfter = $service->passwordStatus($googleSession);
    $assert($googleStatusAfter["mode"] === "change" && $googleStatusAfter["requires_email_code"] === true, "Google account did not switch to normal password-change mode.");
    $expectValidation(
        fn() => $service->updatePassword($googleSession, [
            "password" => "SecondGooglePassword123",
            "password_confirmation" => "SecondGooglePassword123",
        ]),
        "Google account changed an existing password without email verification."
    );

    echo "Password security tests passed." . PHP_EOL;
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $cleanup = $db->prepare("DELETE FROM users WHERE id IN (?, ?)");
    $cleanup->execute([$localId, $googleId]);
}
