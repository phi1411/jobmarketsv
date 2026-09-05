<?php

/**
 * Google Auth Unified Test Runner
 *
 * Runs all Google OAuth test suites sequentially and reports
 * a consolidated pass/fail summary with exit code.
 *
 * Usage: php run_google_auth_tests.php
 *
 * Prerequisites:
 * - .env.testing with APP_ENV=testing and DB_NAME=jobmarket_test
 * - Database 'jobmarket_test' with oauth_identities table migrated
 * - No real Google account or network connection required
 */

$suites = [
    "test_google_auth_p0_01.php" => "GOOGLE-AUTH-P0-01 (OAuth Identity Schema & Repository)",
    "test_google_auth_p0_02.php" => "GOOGLE-AUTH-P0-02 (Server OAuth Callback & JWT Issuance)",
    "test_google_auth_p1_01.php" => "GOOGLE-AUTH-P1-01 (UI Entry Points & Client Handoff)",
    "test_google_auth_p1_02.php" => "GOOGLE-AUTH-P1-02 (Regression Hardening & Isolation)",
];

echo "=================================================================\n";
echo "   GOOGLE AUTH UNIFIED TEST RUNNER\n";
echo "   Running " . count($suites) . " test suites...\n";
echo "=================================================================\n\n";

$results = [];
$allPassed = true;
$startTime = microtime(true);

foreach ($suites as $file => $label) {
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . $file;

    if (!file_exists($filePath)) {
        echo "[SKIP] {$label}: File '{$file}' not found.\n";
        $results[$file] = ["status" => "SKIP", "label" => $label, "exitCode" => -1, "duration" => 0];
        $allPassed = false;
        continue;
    }

    echo "--- {$label} ---\n";
    $suiteStart = microtime(true);

    $escapedPath = escapeshellarg($filePath);
    $output = [];
    $exitCode = 0;
    exec("php {$escapedPath} 2>&1", $output, $exitCode);

    $suiteDuration = round(microtime(true) - $suiteStart, 2);
    $outputStr = implode("\n", $output);

    echo $outputStr . "\n";

    if ($exitCode === 0) {
        $results[$file] = ["status" => "PASS", "label" => $label, "exitCode" => $exitCode, "duration" => $suiteDuration];
    } else {
        $results[$file] = ["status" => "FAIL", "label" => $label, "exitCode" => $exitCode, "duration" => $suiteDuration];
        $allPassed = false;
    }

    echo "\n";
}

$totalDuration = round(microtime(true) - $startTime, 2);

echo "=================================================================\n";
echo "   CONSOLIDATED RESULTS\n";
echo "=================================================================\n";

$passCount = 0;
$failCount = 0;
$skipCount = 0;

foreach ($results as $file => $r) {
    $icon = match ($r["status"]) {
        "PASS" => "[✓]",
        "FAIL" => "[✗]",
        default => "[–]",
    };

    echo "  {$icon} {$r['label']} ({$r['duration']}s)\n";

    match ($r["status"]) {
        "PASS" => $passCount++,
        "FAIL" => $failCount++,
        default => $skipCount++,
    };
}

echo "\n";
echo "  Total: " . count($suites) . " suites | ";
echo "Passed: {$passCount} | Failed: {$failCount} | Skipped: {$skipCount}\n";
echo "  Duration: {$totalDuration}s\n";
echo "=================================================================\n";

if ($allPassed) {
    echo "  ALL GOOGLE AUTH TEST SUITES PASSED!\n";
    echo "=================================================================\n";
    exit(0);
} else {
    echo "  SOME SUITES FAILED. Review output above.\n";
    echo "=================================================================\n";
    exit(1);
}
