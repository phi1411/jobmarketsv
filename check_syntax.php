<?php

$dir = __DIR__;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$phpFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $path = $file->getPathname();
        // Skip vendor directory
        if (str_contains($path, DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR)) {
            continue;
        }
        $phpFiles[] = $path;
    }
}

sort($phpFiles);

echo "Found " . count($phpFiles) . " PHP files to check." . PHP_EOL;

$failed = 0;
$passed = 0;

foreach ($phpFiles as $file) {
    $relPath = str_replace($dir . DIRECTORY_SEPARATOR, "", $file);
    $output = [];
    $exitCode = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $exitCode);

    if ($exitCode !== 0) {
        $failed++;
        echo "[FAILED] $relPath" . PHP_EOL;
        echo "  " . implode(PHP_EOL . "  ", $output) . PHP_EOL;
    } else {
        $passed++;
        echo "[OK] $relPath" . PHP_EOL;
    }
}

echo PHP_EOL . "=== SYNTAX CHECK SUMMARY ===" . PHP_EOL;
echo "Total: " . count($phpFiles) . " | Passed: $passed | Failed: $failed" . PHP_EOL;

exit($failed > 0 ? 1 : 0);
