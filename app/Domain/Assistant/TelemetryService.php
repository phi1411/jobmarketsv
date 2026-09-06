<?php

namespace JobMarket\Domain\Assistant;

class TelemetryService
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        if ($storageDir !== null) {
            $this->storageDir = rtrim($storageDir, "/\\");
        } else {
            $base = defined("BASE_PATH") ? BASE_PATH : dirname(__DIR__, 3);
            $this->storageDir = $base . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "telemetry";
        }
    }

    /**
     * Record request outcome and latency bucket into daily aggregate counters.
     * Absolutely NO message content, response content, user ID, IP address, or token is stored.
     */
    public function recordRequest(string $role, string $outcome, float $durationSeconds): void
    {
        try {
            $date = date("Y-m-d");
            $this->updateMetrics($date, function (array &$data) use ($role, $outcome, $durationSeconds) {
                $data["request_count"]++;

                // Map outcome
                if ($outcome === "success") {
                    $data["success_count"]++;
                } elseif ($outcome === "rate_limit") {
                    $data["rate_limit_count"]++;
                } elseif ($outcome === "safety_blocked") {
                    $data["safety_blocked_count"]++;
                } elseif (in_array($outcome, ["provider_error", "timeout", "service_unavailable"], true)) {
                    $data["provider_error_count"]++;
                }

                // Map latency bucket
                if ($durationSeconds < 1.0) {
                    $data["latency_buckets"]["lt_1s"]++;
                } elseif ($durationSeconds < 3.0) {
                    $data["latency_buckets"]["1s_to_3s"]++;
                } elseif ($durationSeconds < 5.0) {
                    $data["latency_buckets"]["3s_to_5s"]++;
                } elseif ($durationSeconds < 10.0) {
                    $data["latency_buckets"]["5s_to_10s"]++;
                } else {
                    $data["latency_buckets"]["gte_10s"]++;
                }

                // Map role aggregate
                $roleKey = in_array($role, ["company", "student"], true) ? $role : "guest";
                if (isset($data["roles"][$roleKey])) {
                    $data["roles"][$roleKey]++;
                }
            });
        } catch (\Throwable) {
            // Telemetry failure MUST NEVER break the user's chat request
        }
    }

    /**
     * Record thumbs up / down feedback without storing conversation text or user identity.
     */
    public function recordFeedback(string $rating): void
    {
        if ($rating !== "up" && $rating !== "down") {
            return;
        }

        try {
            $date = date("Y-m-d");
            $this->updateMetrics($date, function (array &$data) use ($rating) {
                if ($rating === "up") {
                    $data["feedback"]["thumbs_up"]++;
                } else {
                    $data["feedback"]["thumbs_down"]++;
                }
            });
        } catch (\Throwable) {
            // Telemetry failure MUST NEVER break feedback responses
        }
    }

    /**
     * Get aggregate metrics for a given date (default today).
     */
    public function getDailyMetrics(?string $date = null): ?array
    {
        $date = $date ?? date("Y-m-d");
        $filePath = $this->getFilePath($date);

        if (!file_exists($filePath)) {
            return null;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function getFilePath(string $date): string
    {
        return $this->storageDir . DIRECTORY_SEPARATOR . "chat_metrics_" . $date . ".json";
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }

    private function defaultMetricsStructure(string $date): array
    {
        return [
            "date"                 => $date,
            "request_count"        => 0,
            "success_count"        => 0,
            "provider_error_count" => 0,
            "rate_limit_count"     => 0,
            "safety_blocked_count" => 0,
            "latency_buckets"      => [
                "lt_1s"    => 0,
                "1s_to_3s" => 0,
                "3s_to_5s" => 0,
                "5s_to_10s"=> 0,
                "gte_10s"  => 0,
            ],
            "roles"                => [
                "guest"   => 0,
                "student" => 0,
                "company" => 0,
            ],
            "feedback"             => [
                "thumbs_up"   => 0,
                "thumbs_down" => 0,
            ],
        ];
    }

    /**
     * Updates daily aggregate metrics with atomic file locking.
     *
     * @param string $date
     * @param callable $modifier
     */
    private function updateMetrics(string $date, callable $modifier): void
    {
        $this->ensureDirectory();
        $filePath = $this->getFilePath($date);

        $fp = @fopen($filePath, "c+");
        if (!$fp) {
            return;
        }

        if (!@flock($fp, LOCK_EX)) {
            @fclose($fp);
            return;
        }

        try {
            $content = "";
            while (!feof($fp)) {
                $content .= fread($fp, 8192);
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                $data = $this->defaultMetricsStructure($date);
            }

            // Apply modifier closure
            $modifier($data);

            // Re-write file content atomically
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
        } finally {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }
}
