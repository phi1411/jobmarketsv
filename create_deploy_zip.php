<?php

$zipFile = __DIR__ . '/deploy.zip';
if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "Cannot create zip\n";
    exit(1);
}

$dirs = ['app', 'public', 'vendor', 'scripts'];
// Environment files are intentionally excluded. Production secrets must remain
// only in the hosting account and are never copied into the deployment archive.
$files = ['.htaccess', 'migrate.php', 'dispatch_job_alerts.php', 'dispatch_deadline_reminders.php'];

$baseLen = strlen(__DIR__) + 1;
$fileCount = 0;

foreach ($dirs as $dir) {
    $dirPath = __DIR__ . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($dirPath)) continue;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $relPath = substr($item->getPathname(), $baseLen);
        $subPath = str_replace('\\', '/', $relPath);
        if ($item->isDir()) {
            $zip->addEmptyDir($subPath);
        } else {
            $zip->addFile($item->getPathname(), $subPath);
            $fileCount++;
        }
    }
}

foreach ($files as $file) {
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . $file;
    if (file_exists($filePath)) {
        $zip->addFile($filePath, $file);
        $fileCount++;
    }
}

$zip->close();
echo "Successfully created deploy.zip with {$fileCount} files. Size: " . filesize($zipFile) . " bytes\n";
