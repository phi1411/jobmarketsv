<?php

namespace JobMarket\Domain\Cv;

use RuntimeException;

class CvStorageService
{
    private string $storageDir;

    public function __construct(?string $customStorageDir = null)
    {
        if ($customStorageDir !== null) {
            $this->storageDir = rtrim($customStorageDir, "/\\");
        } else {
            $envPath = $_ENV['CV_STORAGE_PATH'] ?? getenv('CV_STORAGE_PATH');
            if (!empty($envPath)) {
                $this->storageDir = rtrim($envPath, "/\\");
            } else {
                $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
                $this->storageDir = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cvs';
            }
        }

        $this->ensureDirectoryExists();
    }

    public function ensureDirectoryExists(): void
    {
        if (!is_dir($this->storageDir)) {
            if (!@mkdir($this->storageDir, 0755, true) && !is_dir($this->storageDir)) {
                throw new RuntimeException("Không thể khởi tạo thư mục lưu trữ CV riêng tư: {$this->storageDir}");
            }
        }
    }

    public function generateOpaqueFilename(): string
    {
        return bin2hex(random_bytes(16)) . '.pdf';
    }

    /**
     * Stores an uploaded temp file into private storage under an opaque filename.
     * Supports both HTTP move_uploaded_file and CLI test environment copy/rename.
     */
    public function store(string $tempFilePath, ?string $filename = null): string
    {
        $this->ensureDirectoryExists();

        $opaqueFilename = $filename ?? $this->generateOpaqueFilename();
        $targetPath = $this->getAbsolutePath($opaqueFilename);

        if (is_uploaded_file($tempFilePath)) {
            $moved = move_uploaded_file($tempFilePath, $targetPath);
        } else {
            $moved = copy($tempFilePath, $targetPath);
        }

        if (!$moved || !file_exists($targetPath)) {
            throw new RuntimeException("Không thể di chuyển tệp tin vào bộ lưu trữ an toàn.");
        }

        @chmod($targetPath, 0644);

        return $opaqueFilename;
    }

    public function getAbsolutePath(string $storagePath): string
    {
        // Enforce safe basename resolution to strictly prevent directory traversal
        $safeFilename = basename($storagePath);
        return $this->storageDir . DIRECTORY_SEPARATOR . $safeFilename;
    }

    public function fileExists(string $storagePath): bool
    {
        return file_exists($this->getAbsolutePath($storagePath));
    }

    public function deleteFile(string $storagePath): bool
    {
        $targetPath = $this->getAbsolutePath($storagePath);
        if (!file_exists($targetPath)) {
            return true;
        }
        return @unlink($targetPath);
    }

    /**
     * Temporarily renames a file to a quarantine filename within the private storage directory.
     * Returns the quarantine filename on success, or null if the file does not exist.
     * Throws RuntimeException if the rename operation fails.
     */
    public function quarantineFile(string $storagePath): ?string
    {
        $targetPath = $this->getAbsolutePath($storagePath);
        if (!file_exists($targetPath)) {
            return null;
        }

        $safeFilename = basename($storagePath);
        $quarantineFilename = $safeFilename . '.quarantine.' . bin2hex(random_bytes(8));
        $quarantinePath = $this->getAbsolutePath($quarantineFilename);

        $moved = @rename($targetPath, $quarantinePath);
        if (!$moved) {
            throw new RuntimeException("Không thể tạm khóa tệp tin CV trong bộ lưu trữ an toàn.");
        }

        return $quarantineFilename;
    }

    /**
     * Restores a quarantined file back to its original storage path.
     */
    public function restoreQuarantinedFile(string $quarantineFilename, string $originalStoragePath): bool
    {
        $quarantinePath = $this->getAbsolutePath($quarantineFilename);
        $originalPath = $this->getAbsolutePath($originalStoragePath);

        if (!file_exists($quarantinePath)) {
            return false;
        }

        if (@rename($quarantinePath, $originalPath)) {
            return true;
        }

        if (@copy($quarantinePath, $originalPath)) {
            @unlink($quarantinePath);
            return true;
        }

        return false;
    }

    /**
     * Permanently purges a quarantined file.
     */
    public function purgeQuarantinedFile(string $quarantineFilename): bool
    {
        return $this->deleteFile($quarantineFilename);
    }

    public function getStorageDir(): string
    {
        return $this->storageDir;
    }
}
