<?php

namespace JobMarket\Domain;

use JobMarket\Exceptions\AppException;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use PDO;

class AccountSecurityService
{
    private PDO $db;

    public function __construct(private ?MailService $mail = null, ?PDO $db = null)
    {
        $this->mail ??= new MailService();
        if ($db !== null) {
            $this->db = $db;
            return;
        }
        $config = Config::env();
        $this->db = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function passwordStatus(array $sessionUser): array
    {
        $user = $this->userRecord($sessionUser);
        $hasGoogle = $this->hasGoogleIdentity((string)$user["id"]);
        $hasPassword = $user["password_set_at"] !== null || !$hasGoogle;

        return [
            "has_password" => $hasPassword,
            "has_google_login" => $hasGoogle,
            "mode" => $hasPassword ? "change" : "set_first_password",
            "requires_current_password" => $hasPassword,
            "requires_email_code" => $hasPassword,
            "masked_email" => $this->maskEmail((string)$user["email"]),
        ];
    }

    public function requestChangeCode(array $sessionUser, array $data): array
    {
        $user = $this->userRecord($sessionUser);
        $status = $this->passwordStatus($sessionUser);
        if (!$status["has_password"]) {
            throw new ValidationException(["password" => ["Tài khoản Google này chưa có mật khẩu; bạn có thể đặt mật khẩu lần đầu mà không cần mã email."]]);
        }

        $currentPassword = (string)($data["current_password"] ?? "");
        if ($currentPassword === "" || !password_verify($currentPassword, (string)$user["password"])) {
            throw new ValidationException(["current_password" => ["Mật khẩu hiện tại không chính xác."]]);
        }

        $recent = $this->db->prepare(
            "SELECT TIMESTAMPDIFF(SECOND, created_at, CURRENT_TIMESTAMP) AS elapsed_seconds
             FROM password_change_challenges WHERE user_id = ? ORDER BY created_at DESC LIMIT 1"
        );
        $recent->execute([$user["id"]]);
        $elapsedSeconds = $recent->fetchColumn();
        if ($elapsedSeconds !== false && (int)$elapsedSeconds < 60) {
            $retry = max(1, 60 - max(0, (int)$elapsedSeconds));
            throw new AppException("Vui lòng chờ {$retry} giây trước khi gửi mã mới.", 429, ["retry_after_seconds" => $retry]);
        }

        $challengeId = bin2hex(random_bytes(32));
        $code = (string)random_int(100000, 999999);

        $this->db->beginTransaction();
        try {
            $invalidate = $this->db->prepare(
                "UPDATE password_change_challenges SET consumed_at = CURRENT_TIMESTAMP
                 WHERE user_id = ? AND consumed_at IS NULL"
            );
            $invalidate->execute([$user["id"]]);
            $insert = $this->db->prepare(
                "INSERT INTO password_change_challenges (id, user_id, code_hash, expires_at)
                 VALUES (?, ?, ?, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 MINUTE))"
            );
            $insert->execute([$challengeId, $user["id"], password_hash($code, PASSWORD_DEFAULT)]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        $delivery = $this->mail->sendPasswordChangeCode($user, $code, 10);
        if (($delivery["status"] ?? "failed") === "failed") {
            $delete = $this->db->prepare("DELETE FROM password_change_challenges WHERE id = ?");
            $delete->execute([$challengeId]);
            throw new AppException("Không thể gửi mã xác nhận tới email. Vui lòng thử lại sau.", 503);
        }

        $result = [
            "challenge_id" => $challengeId,
            "expires_in_seconds" => 600,
            "masked_email" => $status["masked_email"],
            "delivery_status" => $delivery["status"],
        ];
        if (!Config::isProduction() && !Config::isMailConfigured() && ($delivery["status"] ?? "") === "preview") {
            $result["development_code"] = $code;
        }
        return $result;
    }

    public function verifyChangeCode(array $sessionUser, array $data): array
    {
        $user = $this->userRecord($sessionUser);
        $challengeId = trim((string)($data["challenge_id"] ?? ""));
        $code = trim((string)($data["code"] ?? ""));
        if (!preg_match('/^[a-f0-9]{64}$/', $challengeId) || !preg_match('/^\d{6}$/', $code)) {
            throw new ValidationException(["code" => ["Mã xác nhận không hợp lệ."]]);
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT *, (expires_at > CURRENT_TIMESTAMP) AS is_unexpired
                 FROM password_change_challenges WHERE id = ? AND user_id = ? LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$challengeId, $user["id"]]);
            $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$challenge || $challenge["consumed_at"] !== null || !(bool)$challenge["is_unexpired"]) {
                throw new ValidationException(["code" => ["Mã xác nhận đã hết hạn hoặc không còn hiệu lực."]]);
            }
            if ((int)$challenge["attempts"] >= 5) {
                throw new ValidationException(["code" => ["Bạn đã nhập sai quá nhiều lần. Vui lòng gửi mã mới."]]);
            }

            if (!password_verify($code, (string)$challenge["code_hash"])) {
                $increment = $this->db->prepare("UPDATE password_change_challenges SET attempts = attempts + 1 WHERE id = ?");
                $increment->execute([$challengeId]);
                $this->db->commit();
                throw new ValidationException(["code" => ["Mã xác nhận không chính xác."]]);
            }

            $verify = $this->db->prepare(
                "UPDATE password_change_challenges SET verified_at = CURRENT_TIMESTAMP WHERE id = ? AND consumed_at IS NULL"
            );
            $verify->execute([$challengeId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
        return ["challenge_id" => $challengeId, "verified" => true];
    }

    public function updatePassword(array $sessionUser, array $data): array
    {
        $user = $this->userRecord($sessionUser);
        $status = $this->passwordStatus($sessionUser);
        $password = (string)($data["password"] ?? "");
        $confirmation = (string)($data["password_confirmation"] ?? "");
        $errors = [];
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors["password"][] = "Mật khẩu phải có từ 8 đến 72 ký tự.";
        }
        if (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
            $errors["password"][] = "Mật khẩu phải chứa ít nhất một chữ cái và một chữ số.";
        }
        if ($password !== $confirmation) {
            $errors["password_confirmation"][] = "Mật khẩu nhập lại không khớp.";
        }
        if ($status["has_password"] && password_verify($password, (string)$user["password"])) {
            $errors["password"][] = "Mật khẩu mới phải khác mật khẩu hiện tại.";
        }
        if ($errors !== []) throw new ValidationException($errors);

        if (!$status["has_password"] && !$status["has_google_login"]) {
            throw new AuthenticationException("Không đủ điều kiện đặt mật khẩu lần đầu.");
        }

        $challengeId = trim((string)($data["challenge_id"] ?? ""));
        $this->db->beginTransaction();
        try {
            // Serialize password updates for this account. This prevents two tabs from
            // reusing the same verified one-time challenge concurrently.
            $lockUser = $this->db->prepare("SELECT password_set_at FROM users WHERE id = ? LIMIT 1 FOR UPDATE");
            $lockUser->execute([$user["id"]]);
            $lockedPasswordSetAt = $lockUser->fetchColumn();

            if ($status["has_password"]) {
                $challenge = $this->db->prepare(
                    "SELECT id FROM password_change_challenges
                     WHERE id = ? AND user_id = ? AND verified_at IS NOT NULL AND consumed_at IS NULL AND expires_at > CURRENT_TIMESTAMP
                     LIMIT 1 FOR UPDATE"
                );
                $challenge->execute([$challengeId, $user["id"]]);
                if (!$challenge->fetchColumn()) {
                    throw new ValidationException(["challenge_id" => ["Bạn cần xác nhận mã email trước khi đổi mật khẩu."]]);
                }
            } elseif ($lockedPasswordSetAt !== false && $lockedPasswordSetAt !== null) {
                throw new ValidationException(["password" => ["Tài khoản đã có mật khẩu. Vui lòng mở lại cửa sổ đổi mật khẩu để xác thực."]]);
            }

            $update = $this->db->prepare(
                "UPDATE users SET password = ?, password_set_at = CURRENT_TIMESTAMP, token = 'revoked', token_expires_at = NULL WHERE id = ?"
            );
            $update->execute([password_hash($password, PASSWORD_DEFAULT), $user["id"]]);
            $consume = $this->db->prepare(
                "UPDATE password_change_challenges SET consumed_at = CURRENT_TIMESTAMP WHERE user_id = ? AND consumed_at IS NULL"
            );
            $consume->execute([$user["id"]]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        $this->mail->sendPasswordChangedNotice($user);
        return ["password_updated" => true, "logout_required" => true];
    }

    private function userRecord(array $sessionUser): array
    {
        $id = (string)($sessionUser["id"] ?? "");
        $stmt = $this->db->prepare("SELECT id, name, email, password, password_set_at FROM users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) throw new AuthenticationException();
        return $user;
    }

    private function hasGoogleIdentity(string $userId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM oauth_identities WHERE user_id = ? AND provider = 'google'");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode("@", $email, 2), 2, "");
        $visible = mb_substr($local, 0, min(2, mb_strlen($local, "UTF-8")), "UTF-8");
        return $visible . str_repeat("*", max(2, mb_strlen($local, "UTF-8") - mb_strlen($visible, "UTF-8"))) . "@" . $domain;
    }
}
