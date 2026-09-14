<?php

namespace JobMarket\Domain;

use JobMarket\Facades\Config;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    /** @return array{status:string,error:?string,preview_path:?string} */
    public function sendPasswordChangeCode(array $recipient, string $code, int $validMinutes): array
    {
        $e = fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $subject = "Mã xác nhận đổi mật khẩu JobMarketSV";
        $html = "<!doctype html><html><body style=\"margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#14213d\">"
            . "<div style=\"max-width:560px;margin:24px auto;background:#fff;border:1px solid #e6eaf0;border-radius:16px;overflow:hidden\">"
            . "<div style=\"padding:22px;background:#2563eb;color:#fff;font-size:21px;font-weight:700\">JobMarketSV</div>"
            . "<div style=\"padding:28px\"><p>Chào " . $e($recipient["name"] ?? "bạn") . ",</p>"
            . "<p>Dùng mã sau để xác nhận yêu cầu đổi mật khẩu:</p>"
            . "<div style=\"font-size:34px;letter-spacing:8px;font-weight:800;text-align:center;padding:18px;background:#eff6ff;border-radius:12px;color:#1d4ed8\">" . $e($code) . "</div>"
            . "<p>Mã có hiệu lực {$validMinutes} phút và chỉ sử dụng một lần.</p>"
            . "<p style=\"font-size:12px;color:#64748b\">Nếu bạn không yêu cầu thao tác này, hãy bỏ qua email và kiểm tra lại tài khoản.</p>"
            . "</div></div></body></html>";
        return $this->sendAccountSecurityEmail($recipient, $subject, $html, "Mã xác nhận của bạn là {$code}. Mã có hiệu lực {$validMinutes} phút.", "password-code");
    }

    /** @return array{status:string,error:?string,preview_path:?string} */
    public function sendPasswordChangedNotice(array $recipient): array
    {
        $e = fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $subject = "Mật khẩu JobMarketSV đã được cập nhật";
        $html = "<!doctype html><html><body style=\"margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#14213d\">"
            . "<div style=\"max-width:560px;margin:24px auto;background:#fff;border:1px solid #e6eaf0;border-radius:16px;overflow:hidden\">"
            . "<div style=\"padding:22px;background:#059669;color:#fff;font-size:21px;font-weight:700\">JobMarketSV</div>"
            . "<div style=\"padding:28px\"><p>Chào " . $e($recipient["name"] ?? "bạn") . ",</p>"
            . "<p>Mật khẩu tài khoản của bạn vừa được cập nhật. Các phiên đăng nhập cũ đã bị thu hồi.</p>"
            . "<p style=\"font-size:12px;color:#64748b\">Nếu không phải bạn thực hiện, hãy liên hệ hỗ trợ ngay.</p>"
            . "</div></div></body></html>";
        return $this->sendAccountSecurityEmail($recipient, $subject, $html, "Mật khẩu tài khoản JobMarketSV của bạn vừa được cập nhật.", "password-changed");
    }

    /**
     * @return array{status:string,error:?string,preview_path:?string}
     */
    public function sendJobAlert(array $recipient, array $job, array $search, int $score, array $details): array
    {
        $subject = "Việc làm phù hợp {$score}%: " . ($job["title"] ?? "Cơ hội mới");
        $jobUrl = Config::appUrl() . "/viec-lam/" . rawurlencode((string)$job["id"]);
        $html = $this->renderJobAlertHtml($recipient, $job, $search, $score, $details, $jobUrl);

        if (!Config::isMailConfigured()) {
            return $this->writePreview($recipient, $job, $subject, $html);
        }

        try {
            $config = Config::mail();
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $config["host"];
            $mailer->Port = $config["port"];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config["username"];
            $mailer->Password = $config["password"];
            if ($config["encryption"] !== "" && $config["encryption"] !== "none") {
                $mailer->SMTPSecure = $config["encryption"];
            }
            $mailer->CharSet = "UTF-8";
            $fromAddress = str_ends_with($config["from"], ".local") ? $config["username"] : $config["from"];
            $mailer->setFrom($fromAddress, $config["from_name"]);
            $mailer->addAddress((string)$recipient["email"], (string)($recipient["name"] ?? ""));
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $html;
            $mailer->AltBody = "Việc làm '{$job['title']}' phù hợp {$score}% với bộ lọc '{$search['name']}'. Xem tại: {$jobUrl}";
            $mailer->send();

            return ["status" => "sent", "error" => null, "preview_path" => null];
        } catch (\Throwable $e) {
            return ["status" => "failed", "error" => mb_substr($e->getMessage(), 0, 500), "preview_path" => null];
        }
    }

    /**
     * Sends an alert generated from the student's profile and weekly availability.
     * @return array{status:string,error:?string,preview_path:?string}
     */
    public function sendProfileJobAlert(array $recipient, array $job, int $score, int $coverage, array $details): array
    {
        $subject = "Việc mới phù hợp hồ sơ {$score}%: " . ($job["title"] ?? "Cơ hội mới");
        $jobUrl = Config::appUrl() . "/viec-lam/" . rawurlencode((string)$job["id"]);
        $html = $this->renderProfileJobAlertHtml($recipient, $job, $score, $coverage, $details, $jobUrl);

        if (!Config::isMailConfigured()) {
            return $this->writePreview($recipient, $job, $subject, $html);
        }

        try {
            $config = Config::mail();
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $config["host"];
            $mailer->Port = $config["port"];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config["username"];
            $mailer->Password = $config["password"];
            if ($config["encryption"] !== "" && $config["encryption"] !== "none") {
                $mailer->SMTPSecure = $config["encryption"];
            }
            $mailer->CharSet = "UTF-8";
            $fromAddress = str_ends_with($config["from"], ".local") ? $config["username"] : $config["from"];
            $mailer->setFrom($fromAddress, $config["from_name"]);
            $mailer->addAddress((string)$recipient["email"], (string)($recipient["name"] ?? ""));
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $html;
            $mailer->AltBody = "Việc '{$job['title']}' phù hợp {$score}% với hồ sơ và lịch rảnh của bạn. Xem tại: {$jobUrl}";
            $mailer->send();
            return ["status" => "sent", "error" => null, "preview_path" => null];
        } catch (\Throwable $e) {
            return ["status" => "failed", "error" => mb_substr($e->getMessage(), 0, 500), "preview_path" => null];
        }
    }

    /**
     * Sends an employer decision or interview invitation to a student.
     * @return array{status:string,error:?string,preview_path:?string}
     */
    public function sendApplicationDecision(array $recipient, array $application, string $status, string $message): array
    {
        $label = match ($status) {
            "interview" => "Có lịch phỏng vấn",
            "accepted" => "Bạn đã được chấp nhận",
            "rejected" => "Kết quả ứng tuyển",
            default => "Cập nhật đơn ứng tuyển",
        };
        $subject = $label . ": " . ($application["job_title"] ?? "Vị trí ứng tuyển");
        $applicationUrl = Config::appUrl() . "/student/applications";
        $html = $this->renderApplicationDecisionHtml($recipient, $application, $status, $message, $applicationUrl);

        if (!Config::isMailConfigured()) {
            return $this->writePreview($recipient, ["id" => "application-" . ($application["id"] ?? "decision")], $subject, $html);
        }

        try {
            $config = Config::mail();
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $config["host"];
            $mailer->Port = $config["port"];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config["username"];
            $mailer->Password = $config["password"];
            if ($config["encryption"] !== "" && $config["encryption"] !== "none") {
                $mailer->SMTPSecure = $config["encryption"];
            }
            $mailer->CharSet = "UTF-8";
            $fromAddress = str_ends_with($config["from"], ".local") ? $config["username"] : $config["from"];
            $mailer->setFrom($fromAddress, $config["from_name"]);
            $mailer->addAddress((string)$recipient["email"], (string)($recipient["name"] ?? ""));
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $html;
            $mailer->AltBody = $label . " cho vị trí '" . ($application["job_title"] ?? "") . "'. Nội dung từ nhà tuyển dụng: " . $message . ". Xem tại: " . $applicationUrl;
            $mailer->send();
            return ["status" => "sent", "error" => null, "preview_path" => null];
        } catch (\Throwable $e) {
            return ["status" => "failed", "error" => mb_substr($e->getMessage(), 0, 500), "preview_path" => null];
        }
    }

    private function renderApplicationDecisionHtml(array $recipient, array $application, string $status, string $message, string $applicationUrl): string
    {
        $e = fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $heading = match ($status) {
            "interview" => "Bạn có lịch phỏng vấn mới",
            "accepted" => "Chúc mừng, bạn đã được chấp nhận",
            "rejected" => "Nhà tuyển dụng đã phản hồi đơn ứng tuyển",
            default => "Đơn ứng tuyển có cập nhật mới",
        };
        $accent = match ($status) { "accepted" => "#059669", "rejected" => "#dc2626", default => "#2563eb" };

        return "<!doctype html><html><body style=\"margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#14213d\">"
            . "<div style=\"max-width:620px;margin:24px auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e6eaf0\">"
            . "<div style=\"padding:24px;background:" . $accent . ";color:#fff\"><strong style=\"font-size:22px\">JobMarketSV</strong><div style=\"margin-top:8px\">" . $e($heading) . "</div></div>"
            . "<div style=\"padding:28px\"><p>Chào " . $e($recipient["name"] ?? "bạn") . ",</p>"
            . "<p>Nhà tuyển dụng <strong>" . $e($application["company_name"] ?? "Doanh nghiệp") . "</strong> đã cập nhật đơn ứng tuyển vị trí <strong>" . $e($application["job_title"] ?? "") . "</strong>.</p>"
            . "<div style=\"margin:20px 0;padding:18px;border-left:4px solid " . $accent . ";background:#f8fafc;border-radius:10px;white-space:pre-line\"><strong>Nội dung từ nhà tuyển dụng:</strong><br><br>" . nl2br($e($message)) . "</div>"
            . "<p><a href=\"" . $e($applicationUrl) . "\" style=\"display:inline-block;background:" . $accent . ";color:#fff;text-decoration:none;padding:12px 20px;border-radius:9px;font-weight:700\">Xem đơn ứng tuyển</a></p>"
            . "<p style=\"margin-top:28px;font-size:12px;color:#64748b\">Đây là thông báo giao dịch liên quan trực tiếp đến đơn ứng tuyển của bạn trên JobMarketSV.</p>"
            . "</div></div></body></html>";
    }

    private function renderJobAlertHtml(array $recipient, array $job, array $search, int $score, array $details, string $jobUrl): string
    {
        $e = fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $reasonItems = "";
        foreach ($details as $detail) {
            if (($detail["matched"] ?? false) === true) {
                $reasonItems .= "<li style=\"margin:6px 0\">" . $e($detail["label"] ?? "Tiêu chí phù hợp") . "</li>";
            }
        }

        return "<!doctype html><html><body style=\"margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#14213d\">"
            . "<div style=\"max-width:620px;margin:24px auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e6eaf0\">"
            . "<div style=\"padding:24px;background:#2563eb;color:#fff\"><strong style=\"font-size:22px\">JobMarketSV</strong><div style=\"margin-top:8px\">Có việc làm mới dành cho bạn</div></div>"
            . "<div style=\"padding:28px\"><p>Chào " . $e($recipient["name"] ?? "bạn") . ",</p>"
            . "<p>Tin <strong>" . $e($job["title"] ?? "") . "</strong> tại <strong>" . $e($job["company_name"] ?? "Nhà tuyển dụng") . "</strong> phù hợp với bộ lọc <strong>" . $e($search["name"] ?? "Tìm kiếm đã lưu") . "</strong>.</p>"
            . "<div style=\"margin:20px 0;padding:16px;border-radius:12px;background:#eff6ff;text-align:center\"><div style=\"font-size:13px;color:#475569\">Mức độ phù hợp</div><div style=\"font-size:36px;font-weight:800;color:#2563eb\">{$score}%</div></div>"
            . ($reasonItems !== "" ? "<p><strong>Điểm phù hợp nổi bật</strong></p><ul style=\"padding-left:20px;color:#475569\">{$reasonItems}</ul>" : "")
            . "<p style=\"margin-top:26px\"><a href=\"" . $e($jobUrl) . "\" style=\"display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 20px;border-radius:9px;font-weight:700\">Xem việc làm</a></p>"
            . "<p style=\"margin-top:28px;font-size:12px;color:#64748b\">Bạn nhận email này vì đã bật thông báo cho bộ lọc tìm kiếm trên JobMarketSV. Bạn có thể tắt email tại trang Tìm kiếm đã lưu.</p>"
            . "</div></div></body></html>";
    }

    private function renderProfileJobAlertHtml(array $recipient, array $job, int $score, int $coverage, array $details, string $jobUrl): string
    {
        $e = fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $reasonItems = "";
        foreach ($details as $detail) {
            if (($detail["matched"] ?? false) === true) {
                $reasonItems .= "<li style=\"margin:6px 0\">" . $e($detail["label"] ?? "Tiêu chí phù hợp") . ": " . $e($detail["score"] ?? "") . "%</li>";
            }
        }
        return "<!doctype html><html><body style=\"margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#14213d\">"
            . "<div style=\"max-width:620px;margin:24px auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e6eaf0\">"
            . "<div style=\"padding:24px;background:#2563eb;color:#fff\"><strong style=\"font-size:22px\">JobMarketSV</strong><div style=\"margin-top:8px\">Việc mới phù hợp với hồ sơ của bạn</div></div>"
            . "<div style=\"padding:28px\"><p>Chào " . $e($recipient["name"] ?? "bạn") . ",</p>"
            . "<p><strong>" . $e($job["title"] ?? "") . "</strong> tại <strong>" . $e($job["company_name"] ?? "Nhà tuyển dụng") . "</strong> phù hợp với kỹ năng, khu vực và lịch rảnh bạn đã khai.</p>"
            . "<div style=\"margin:20px 0;padding:16px;border-radius:12px;background:#eff6ff;text-align:center\"><div style=\"font-size:13px;color:#475569\">Mức độ phù hợp</div><div style=\"font-size:36px;font-weight:800;color:#2563eb\">{$score}%</div><div style=\"font-size:12px;color:#64748b\">Dữ liệu đối chiếu {$coverage}%</div></div>"
            . ($reasonItems !== "" ? "<p><strong>Điểm phù hợp nổi bật</strong></p><ul style=\"padding-left:20px;color:#475569\">{$reasonItems}</ul>" : "")
            . "<p style=\"margin-top:26px\"><a href=\"" . $e($jobUrl) . "\" style=\"display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 20px;border-radius:9px;font-weight:700\">Xem việc làm</a></p>"
            . "<p style=\"margin-top:28px;font-size:12px;color:#64748b\">Bạn nhận email vì đã bật Thông báo cá nhân hóa tại trang Gợi Ý Cho Bạn. Bạn có thể tắt bất cứ lúc nào.</p>"
            . "</div></div></body></html>";
    }

    private function writePreview(array $recipient, array $job, string $subject, string $html): array
    {
        $dir = BASE_PATH . "/storage/app/mail-preview";
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)($job["id"] ?? "job"));
        $path = $dir . "/" . date("Ymd-His") . "-" . $safeId . ".html";
        $meta = "<!-- To: " . htmlspecialchars((string)($recipient["email"] ?? ""), ENT_QUOTES) . " | Subject: " . htmlspecialchars($subject, ENT_QUOTES) . " -->\n";
        @file_put_contents($path, $meta . $html);

        return ["status" => "preview", "error" => null, "preview_path" => $path];
    }

    private function sendAccountSecurityEmail(array $recipient, string $subject, string $html, string $altBody, string $previewId): array
    {
        if (!Config::isMailConfigured()) {
            return $this->writePreview($recipient, ["id" => $previewId], $subject, $html);
        }
        try {
            $config = Config::mail();
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $config["host"];
            $mailer->Port = $config["port"];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config["username"];
            $mailer->Password = $config["password"];
            if ($config["encryption"] !== "" && $config["encryption"] !== "none") $mailer->SMTPSecure = $config["encryption"];
            $mailer->CharSet = "UTF-8";
            $fromAddress = str_ends_with($config["from"], ".local") ? $config["username"] : $config["from"];
            $mailer->setFrom($fromAddress, $config["from_name"]);
            $mailer->addAddress((string)$recipient["email"], (string)($recipient["name"] ?? ""));
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $html;
            $mailer->AltBody = $altBody;
            $mailer->send();
            return ["status" => "sent", "error" => null, "preview_path" => null];
        } catch (\Throwable $e) {
            return ["status" => "failed", "error" => mb_substr($e->getMessage(), 0, 500), "preview_path" => null];
        }
    }
}
