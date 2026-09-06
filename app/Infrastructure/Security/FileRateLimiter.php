<?php

namespace JobMarket\Infrastructure\Security;

use JobMarket\Domain\Assistant\RateLimiterInterface;
use JobMarket\Domain\Assistant\RateLimitResult;

class FileRateLimiter implements RateLimiterInterface
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        if ($storageDir !== null) {
            $this->storageDir = rtrim($storageDir, "/\\");
        } else {
            $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
            $this->storageDir = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'rate_limits';
        }

        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }

    private function getFilePath(string $key): string
    {
        $safeName = 'rl_' . hash('sha256', $key) . '.json';
        return $this->storageDir . DIRECTORY_SEPARATOR . $safeName;
    }

    public function consume(string $key, int $maxAttempts, int $decaySeconds = 60): RateLimitResult
    {
        // 1. Validate storage directory
        if (!is_dir($this->storageDir)) {
            if (!@mkdir($this->storageDir, 0755, true) && !is_dir($this->storageDir)) {
                return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
            }
        }
        if (!is_writable($this->storageDir)) {
            return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
        }

        $file = $this->getFilePath($key);
        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
        }

        // 2. Exclusive per-key lock for entire read/expiry/check/increment/write sequence
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
        }

        try {
            $now = time();
            $content = stream_get_contents($fp);

            if ($content === false) {
                return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
            }

            $content = trim($content);

            if ($content === '') {
                // First hit in a new window
                if ($maxAttempts < 1) {
                    return new RateLimitResult(allowed: false, remaining: 0, retryAfter: $decaySeconds, failedClosed: false);
                }

                $data = [
                    'attempts'   => 1,
                    'expires_at' => $now + $decaySeconds,
                    'first_hit'  => $now
                ];

                $json = json_encode($data, JSON_UNESCAPED_UNICODE);
                ftruncate($fp, 0);
                rewind($fp);
                $written = fwrite($fp, $json);
                fflush($fp);

                if ($written === false || $written !== strlen($json)) {
                    return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
                }

                return new RateLimitResult(allowed: true, remaining: $maxAttempts - 1, retryAfter: 0, failedClosed: false);
            }

            // Existing record: decode JSON
            $data = json_decode($content, true);
            if (!is_array($data) || !isset($data['attempts']) || !isset($data['expires_at'])) {
                // Decode or corrupted format -> fail closed safely
                return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
            }

            // Check expiry
            if ($now >= $data['expires_at']) {
                // Window expired: start fresh window
                if ($maxAttempts < 1) {
                    return new RateLimitResult(allowed: false, remaining: 0, retryAfter: $decaySeconds, failedClosed: false);
                }

                $data = [
                    'attempts'   => 1,
                    'expires_at' => $now + $decaySeconds,
                    'first_hit'  => $now
                ];

                $json = json_encode($data, JSON_UNESCAPED_UNICODE);
                ftruncate($fp, 0);
                rewind($fp);
                $written = fwrite($fp, $json);
                fflush($fp);

                if ($written === false || $written !== strlen($json)) {
                    return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
                }

                return new RateLimitResult(allowed: true, remaining: $maxAttempts - 1, retryAfter: 0, failedClosed: false);
            }

            // Window active: check threshold
            $retryAfter = max(1, (int)($data['expires_at'] - $now));

            if ($data['attempts'] >= $maxAttempts) {
                // Real limit exhaustion -> denied with accurate Retry-After, not a storage failure
                return new RateLimitResult(allowed: false, remaining: 0, retryAfter: $retryAfter, failedClosed: false);
            }

            // Increment attempt
            $data['attempts']++;
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            ftruncate($fp, 0);
            rewind($fp);
            $written = fwrite($fp, $json);
            fflush($fp);

            if ($written === false || $written !== strlen($json)) {
                return new RateLimitResult(allowed: false, remaining: 0, retryAfter: 0, failedClosed: true);
            }

            $remaining = max(0, $maxAttempts - $data['attempts']);
            return new RateLimitResult(allowed: true, remaining: $remaining, retryAfter: 0, failedClosed: false);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return false;
        }

        $fp = @fopen($file, 'r');
        if ($fp === false) {
            return true; // fail closed
        }

        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            return true; // fail closed
        }

        try {
            $content = stream_get_contents($fp);
            if ($content === false || empty($content)) {
                return true; // fail closed
            }

            $data = json_decode($content, true);
            if (!is_array($data) || !isset($data['attempts']) || !isset($data['expires_at'])) {
                return true; // fail closed
            }

            if (time() >= $data['expires_at']) {
                return false;
            }

            return $data['attempts'] >= $maxAttempts;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function hit(string $key, int $decaySeconds = 60): int
    {
        $res = $this->consume($key, PHP_INT_MAX, $decaySeconds);
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $data = json_decode((string)$content, true);
            if (is_array($data) && isset($data['attempts'])) {
                return (int)$data['attempts'];
            }
        }
        return 1;
    }

    public function availableIn(string $key): int
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return 0;
        }

        $fp = @fopen($file, 'r');
        if ($fp === false) {
            return 60; // safe fallback
        }

        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            return 60;
        }

        try {
            $content = stream_get_contents($fp);
            if ($content === false || empty($content)) {
                return 0;
            }

            $data = json_decode($content, true);
            if (!is_array($data) || !isset($data['expires_at'])) {
                return 0;
            }

            $remaining = $data['expires_at'] - time();
            return max(0, (int)$remaining);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function clear(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
