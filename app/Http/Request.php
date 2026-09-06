<?php

namespace JobMarket\Http;

use JobMarket\Facades\Config;

class Request
{
    private ?array $user = null;

    public function __construct(
        public readonly array $getParams,
        public readonly array $postParams,
        public readonly array $cookie,
        public readonly array $files,
        public readonly array $server
    ) {
    }

    public static function createFromGlobals(): static
    {
        $get = $_GET;
        $post = $_POST;
        $cookie = $_COOKIE;
        $files = $_FILES;
        $server = $_SERVER;

        // Parse JSON Body for API requests (POST, PUT, PATCH, DELETE)
        $contentType = $server["CONTENT_TYPE"] ?? "";
        if (str_contains($contentType, "application/json") || empty($post)) {
            $rawInput = file_get_contents("php://input");
            if (!empty($rawInput)) {
                $decoded = json_decode($rawInput, true);
                if (is_array($decoded)) {
                    $post = array_merge($post, $decoded);
                }
            }
        }

        return new static($get, $post, $cookie, $files, $server);
    }

    public function getPathInfo(): string
    {
        $uri = $this->server["REQUEST_URI"] ?? "/";
        return strtok($uri, "?") ?: "/";
    }

    public function getMethod(): string
    {
        return strtoupper($this->server["REQUEST_METHOD"] ?? "GET");
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $this->getParams[$key] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function all(): array
    {
        return array_merge($this->getParams, $this->postParams);
    }

    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function wantsHtml(): bool
    {
        $accept = $this->server["HTTP_ACCEPT"] ?? "";
        $contentType = $this->server["CONTENT_TYPE"] ?? "";

        if (str_contains($accept, "application/json") || str_contains($contentType, "application/json")) {
            return false;
        }

        return str_contains($accept, "text/html");
    }

    public function getClientIp(?array $trustedProxies = null): string
    {
        $remoteAddr = $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            $remoteAddr = '127.0.0.1';
        }

        $trusted = $trustedProxies ?? (class_exists(Config::class) ? Config::trustedProxies() : []);

        // Inspect X-Forwarded-For only when REMOTE_ADDR is explicitly listed in TRUSTED_PROXIES
        if (!empty($trusted) && in_array($remoteAddr, $trusted, true)) {
            if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
                $rawIps = explode(',', (string)$this->server['HTTP_X_FORWARDED_FOR']);
                $parsedIps = [];
                foreach ($rawIps as $ipStr) {
                    $trimmed = trim($ipStr);
                    if (filter_var($trimmed, FILTER_VALIDATE_IP)) {
                        $parsedIps[] = $trimmed;
                    }
                }

                // Traverse from right to left: skip trusted proxies, pick first valid non-trusted IP
                for ($i = count($parsedIps) - 1; $i >= 0; $i--) {
                    $ip = $parsedIps[$i];
                    if (in_array($ip, $trusted, true)) {
                        continue;
                    }
                    return $ip;
                }
            }
        }

        // Default / fallback to REMOTE_ADDR when no safe client IP exists
        return $remoteAddr;
    }
}
