<?php

declare(strict_types=1);

/**
 * CV-AI Phase P0 Master Test Runner
 * Executes all regression test suites across P0-01 to P0-05.
 */

echo "=================================================================\n";
echo "   JOBMARKETSV - CV / PROFILE <-> JOB MATCHING P0 MASTER SUITE   \n";
echo "=================================================================\n\n";

$suites = [
    'CV-AI-P0-01 (Contracts & Missing Data)' => 'test_matching_p0_01.php',
    'CV-AI-P0-02 (Adapters, Redaction & HMAC)' => 'test_matching_p0_02.php',
    'CV-AI-P0-03 (Deterministic Matcher v1)' => 'test_matching_p0_03.php',
    'CV-AI-P0-04 & P0-05 (Consent, Migration, Entity & Apply Flow)' => 'test_matching_p0_04_p0_05.php',
];

$allPassed = true;

foreach ($suites as $name => $file) {
    echo ">>> Running {$name}...\n";
    $output = [];
    $exitCode = 0;
    exec("php " . escapeshellarg(__DIR__ . '/' . $file), $output, $exitCode);
    echo implode("\n", $output) . "\n\n";
    if ($exitCode !== 0) {
        $allPassed = false;
        echo "[ERROR] Suite {$name} failed with exit code {$exitCode}!\n\n";
    }
}

if ($allPassed) {
    echo "=================================================================\n";
    echo "   [SUCCESS] ALL CV-AI-P0 SUITES PASSED FLAWLESSLY!              \n";
    echo "=================================================================\n";
    exit(0);
} else {
    echo "=================================================================\n";
    echo "   [FAILURE] SOME SUITES FAILED. PLEASE CHECK OUTPUT ABOVE.       \n";
    echo "=================================================================\n";
    exit(1);
}