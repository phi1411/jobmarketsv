<?php

namespace JobMarket\Http;

class Response
{
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_NO_CONTENT = 204;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_CONFLICT = 409;
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    public static array $statusTexts = [
        200 => "OK",
        201 => "Created",
        204 => "No Content",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        409 => "Conflict",
        422 => "Unprocessable Entity",
        500 => "Internal Server Error"
    ];

    private mixed $payload;
    private int $statusCode;
    private array $headers;
    private bool $isHtml = false;

    public function __construct(mixed $data = [], int $statusCode = self::HTTP_OK, array $headers = [], bool $isHtml = false)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->isHtml = $isHtml;

        if ($isHtml) {
            $this->payload = $data;
            return;
        }

        // Auto-wrap legacy arrays if not already in standard format
        if (is_array($data) && isset($data["success"])) {
            $this->payload = $data;
        } elseif ($statusCode >= 400) {
            $message = is_array($data) && isset($data["message"]) ? $data["message"] : (self::$statusTexts[$statusCode] ?? "Error");
            $errors = is_array($data) && isset($data["errors"]) ? $data["errors"] : null;
            $this->payload = [
                "success" => false,
                "message" => $message,
                "errors"  => $errors,
                "code"    => $statusCode
            ];
        } else {
            $this->payload = [
                "success" => true,
                "message" => is_array($data) && isset($data["message"]) ? $data["message"] : "Thành công",
                "data"    => is_array($data) && isset($data["data"]) ? $data["data"] : $data,
                "meta"    => is_array($data) && isset($data["meta"]) ? $data["meta"] : null
            ];
        }
    }

    public static function success(mixed $data = null, string $message = "Thao tác thành công", int $statusCode = self::HTTP_OK, ?array $meta = null): static
    {
        $payload = [
            "success" => true,
            "message" => $message,
            "data"    => $data,
            "meta"    => $meta
        ];

        return new static($payload, $statusCode);
    }

    public static function created(mixed $data = null, string $message = "Tạo mới thành công", ?array $meta = null): static
    {
        return self::success($data, $message, self::HTTP_CREATED, $meta);
    }

    public static function error(string $message = "Đã có lỗi xảy ra", int $statusCode = self::HTTP_BAD_REQUEST, ?array $errors = null): static
    {
        $payload = [
            "success" => false,
            "message" => $message,
            "errors"  => $errors,
            "code"    => $statusCode
        ];

        return new static($payload, $statusCode);
    }

    public static function html(string $html, int $statusCode = self::HTTP_OK, array $headers = []): static
    {
        return new static($html, $statusCode, $headers, true);
    }

    public static function redirect(string $url, int $statusCode = 302): static
    {
        return new static("", $statusCode, ["Location" => $url], true);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return (string)$value;
            }
        }
        return null;
    }

    public function send(): void
    {
        // Set HTTP Status Code
        http_response_code($this->statusCode);

        // Apply CORS Headers from Environment
        $origin = $_ENV["CORS_ALLOWED_ORIGINS"] ?? "*";
        $methods = $_ENV["CORS_ALLOWED_METHODS"] ?? "GET, POST, PUT, PATCH, DELETE, OPTIONS";
        $headers = $_ENV["CORS_ALLOWED_HEADERS"] ?? "Content-Type, Authorization, X-Requested-With";

        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Methods: " . $methods);
        header("Access-Control-Allow-Headers: " . $headers);
        header("Access-Control-Allow-Credentials: true");

        // Custom headers
        foreach ($this->headers as $header => $value) {
            header("{$header}: {$value}");
        }

        // Return empty body for 204 No Content or Redirect
        if ($this->statusCode === self::HTTP_NO_CONTENT || $this->statusCode === 301 || $this->statusCode === 302) {
            return;
        }

        if ($this->isHtml) {
            header("Content-Type: text/html; charset=utf-8");
            header("X-Content-Type-Options: nosniff");
            header("X-Frame-Options: SAMEORIGIN");
            header("Referrer-Policy: strict-origin-when-cross-origin");
            echo is_string($this->payload) ? $this->payload : "";
            return;
        }

        header("Content-Type: application/json; charset=utf-8");
        header("X-Content-Type-Options: nosniff");
        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
