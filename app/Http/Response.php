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
    const HTTP_TOO_MANY_REQUESTS = 429;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;

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
        429 => "Too Many Requests",
        500 => "Internal Server Error",
        503 => "Service Unavailable",
        504 => "Gateway Timeout"
    ];

    private mixed $payload;
    private int $statusCode;
    private array $headers;
    private bool $isHtml = false;
    private bool $isFile = false;
    private bool $isBinary = false;
    private ?string $filePath = null;

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

    public static function error(string $message = "Đã có lỗi xảy ra", int $statusCode = self::HTTP_BAD_REQUEST, ?array $errors = null, array $headers = []): static
    {
        $payload = [
            "success" => false,
            "message" => $message,
            "errors"  => $errors,
            "code"    => $statusCode
        ];

        return new static($payload, $statusCode, $headers);
    }

    public static function html(string $html, int $statusCode = self::HTTP_OK, array $headers = []): static
    {
        return new static($html, $statusCode, $headers, true);
    }

    public static function file(
        string $filePath,
        string $fileName = "document.pdf",
        string $mimeType = "application/pdf",
        bool $inline = true,
        array $headers = []
    ): static {
        $asciiName = preg_replace('/[^\x20-\x7E]/', '', $fileName);
        if (empty($asciiName)) {
            $asciiName = "document.pdf";
        }
        $encodedName = rawurlencode($fileName);
        $dispositionType = $inline ? 'inline' : 'attachment';
        $contentDisposition = "{$dispositionType}; filename=\"{$asciiName}\"; filename*=UTF-8''{$encodedName}";

        $defaultHeaders = [
            "Content-Type"           => $mimeType,
            "Content-Disposition"    => $contentDisposition,
            "X-Content-Type-Options" => "nosniff",
            "Cache-Control"          => "private, no-cache, no-store, must-revalidate",
            "Pragma"                 => "no-cache",
            "Expires"                => "0",
        ];

        if (file_exists($filePath)) {
            $defaultHeaders["Content-Length"] = (string)filesize($filePath);
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        $response = new static("", self::HTTP_OK, $allHeaders, false);
        $response->isFile = true;
        $response->filePath = $filePath;
        return $response;
    }

    public static function binary(string $content, string $mimeType, array $headers = [], int $statusCode = self::HTTP_OK): static
    {
        $response = new static("", $statusCode, array_merge([
            "Content-Type" => $mimeType,
            "Content-Length" => (string)strlen($content),
            "X-Content-Type-Options" => "nosniff",
        ], $headers), true);
        $response->payload = $content;
        $response->isHtml = false;
        $response->isBinary = true;
        return $response;
    }

    public static function redirect(string $url, int $statusCode = 302): static
    {
        return new static("", $statusCode, ["Location" => $url], true);
    }

    public function isFile(): bool
    {
        return $this->isFile;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
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

    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
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

        if ($this->isFile && $this->filePath !== null) {
            if (file_exists($this->filePath)) {
                readfile($this->filePath);
            }
            return;
        }

        if ($this->isBinary) {
            echo is_string($this->payload) ? $this->payload : "";
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
