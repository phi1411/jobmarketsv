<?php

namespace JobMarket\Domain\Cv;

use JobMarket\Domain\Profile\Profile;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\ProfileRepository;
use PDO;
use RuntimeException;
use Throwable;

class StudentCvService
{
    public const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    private CvStorageService $storageService;
    private ProfileRepositoryInterface $profileRepository;
    private PDO $db;

    public function __construct(
        ?CvStorageService $storageService = null,
        ?ProfileRepositoryInterface $profileRepository = null,
        ?PDO $db = null
    ) {
        $this->storageService = $storageService ?? new CvStorageService();
        $this->profileRepository = $profileRepository ?? new ProfileRepository();
        if ($db !== null) {
            $this->db = $db;
        } else {
            $config = Config::env();
            $this->db = new PDO(
                "mysql:dbname={$config['dbname']};host={$config['host']}",
                $config["user"],
                $config["password"],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
    }

    /**
     * Get the active CV metadata for the authenticated student.
     * Returns null if no active CV has been uploaded.
     */
    public function getActiveCv(array $user): ?array
    {
        $this->enforceStudentRole($user);

        $userId = (string)($user['id'] ?? '');
        $existing = $this->profileRepository->findByUserId($userId);

        if (!$existing || empty($existing['cv_storage_path'])) {
            return null;
        }

        return [
            'file_name'   => $existing['cv_original_name'] ?? 'cv.pdf',
            'file_size'   => isset($existing['cv_file_size']) && $existing['cv_file_size'] !== null ? (int)$existing['cv_file_size'] : 0,
            'mime_type'   => $existing['cv_mime_type'] ?? 'application/pdf',
            'uploaded_at' => $existing['cv_uploaded_at'] ?? null,
        ];
    }

    /**
     * Upload or replace the student's active CV with strict security & MIME validation.
     */
    public function uploadActiveCv(array $user, ?array $file): array
    {
        $this->enforceStudentRole($user);

        // 1. Validate file presence
        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(
                ['cv_file' => ['Vui lòng chọn tệp tin CV định dạng PDF để tải lên.']],
                'Vui lòng chọn tệp tin CV để tải lên.'
            );
        }

        // 2. Validate upload errors
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new ValidationException(
                ['cv_file' => ['Dung lượng tệp CV vượt quá giới hạn cho phép (tối đa 5 MB).']],
                'Dung lượng tệp CV vượt quá giới hạn 5 MB.'
            );
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(
                ['cv_file' => ["Lỗi khi tải tệp lên máy chủ (mã lỗi: {$file['error']})."]],
                'Lỗi khi tải lên tệp tin CV.'
            );
        }

        // 3. Validate temp file existence
        $tmpName = $file['tmp_name'] ?? '';
        if (empty($tmpName) || !file_exists($tmpName)) {
            throw new ValidationException(
                ['cv_file' => ['Tệp tạm thời không tồn tại trên máy chủ.']],
                'Tệp tải lên không hợp lệ.'
            );
        }

        // 4. Validate file size
        $fileSize = (int)($file['size'] ?? filesize($tmpName));
        if ($fileSize <= 0) {
            throw new ValidationException(
                ['cv_file' => ['Tệp CV rỗng hoặc không có nội dung.']],
                'Tệp tin rỗng không hợp lệ.'
            );
        }

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new ValidationException(
                ['cv_file' => ['Dung lượng tệp CV không được vượt quá 5 MB.']],
                'Dung lượng tệp CV không được vượt quá 5 MB.'
            );
        }

        // 5. Validate extension
        $origName = (string)($file['name'] ?? 'cv.pdf');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new ValidationException(
                ['cv_file' => ['Định dạng tệp không hợp lệ. Hệ thống chỉ chấp nhận tệp có phần mở rộng .pdf.']],
                'Chỉ chấp nhận tệp định dạng PDF.'
            );
        }

        // 6. Server-side MIME type verification via Fileinfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpName);
        if ($detectedMime !== 'application/pdf') {
            throw new ValidationException(
                ['cv_file' => ['Nội dung tệp không phải định dạng PDF hợp lệ (MIME type không hợp lệ).']],
                'Tệp tải lên không phải là PDF hợp lệ.'
            );
        }

        // 7. Magic bytes header verification (%PDF-)
        $fh = @fopen($tmpName, 'rb');
        $header = $fh ? fread($fh, 4) : '';
        if ($fh) {
            fclose($fh);
        }
        if ($header !== '%PDF') {
            throw new ValidationException(
                ['cv_file' => ['Nội dung tệp bị lỗi hoặc không có cấu trúc tệp PDF hợp lệ.']],
                'Tệp tin không đúng cấu trúc PDF hợp lệ.'
            );
        }

        // 8. Store file securely outside webroot with opaque name
        $storageFilename = $this->storageService->store($tmpName);

        try {
            // 9. Persist metadata into student_profiles
            $userId = (string)($user['id'] ?? '');
            $existing = $this->profileRepository->findByUserId($userId);
            $profile = $existing ? Profile::fromArray($existing) : new Profile($userId);

            $uploadedAt = date('Y-m-d H:i:s');
            $safeOriginalName = basename($origName);

            $profile->setCvStoragePath($storageFilename);
            $profile->setCvOriginalName($safeOriginalName);
            $profile->setCvMimeType('application/pdf');
            $profile->setCvFileSize($fileSize);
            $profile->setCvUploadedAt($uploadedAt);

            $this->profileRepository->upsert($profile);
        } catch (Throwable $e) {
            // Persistence failed! Delete only the newly written file to prevent orphan files
            $this->storageService->deleteFile($storageFilename);
            // Rethrow the original failure - existing active CV (if any) remains unaltered
            throw $e;
        }

        // 10. Return owner-safe metadata (never expose raw storage path)
        return [
            'file_name'   => $safeOriginalName,
            'file_size'   => $fileSize,
            'mime_type'   => 'application/pdf',
            'uploaded_at' => $uploadedAt,
        ];
    }

    /**
     * Delete/detach the active CV from student profile and remove the physical file if unreferenced.
     * Uses a failure-safe quarantine rename with rollback restoration to guarantee two-phase consistency.
     */
    public function deleteActiveCv(array $user): void
    {
        $this->enforceStudentRole($user);

        $userId = (string)($user['id'] ?? '');
        $existing = $this->profileRepository->findByUserId($userId);
        if (!$existing || empty($existing['cv_storage_path'])) {
            return;
        }

        $activeStoragePath = (string)$existing['cv_storage_path'];

        // Check if any other student profile references the same file
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `student_profiles` WHERE `cv_storage_path` = ? AND `user_id` != ?"
        );
        $stmt->execute([$activeStoragePath, $userId]);
        $hasOtherReference = ((int)$stmt->fetchColumn()) > 0;

        $quarantineFilename = null;

        // If no other profile references this active file, quarantine it before updating database
        if (!$hasOtherReference && $this->storageService->fileExists($activeStoragePath)) {
            $quarantineFilename = $this->storageService->quarantineFile($activeStoragePath);
        }

        try {
            $profile = Profile::fromArray($existing);
            $profile->setCvStoragePath(null);
            $profile->setCvOriginalName(null);
            $profile->setCvMimeType(null);
            $profile->setCvFileSize(null);
            $profile->setCvUploadedAt(null);

            $this->profileRepository->upsert($profile);
        } catch (Throwable $e) {
            // Rollback: if database persistence failed, attempt to restore the quarantined file
            if ($quarantineFilename !== null) {
                $restored = $this->storageService->restoreQuarantinedFile($quarantineFilename, $activeStoragePath);
                if (!$restored) {
                    // Restore failed! The physical file remains at $quarantineFilename.
                    // Do not leave student_profiles pointing to $activeStoragePath (which is missing).
                    // Update database to point to $quarantineFilename so the record matches the actual file on disk.
                    try {
                        $recoverProfile = Profile::fromArray($existing);
                        $recoverProfile->setCvStoragePath($quarantineFilename);
                        $this->profileRepository->upsert($recoverProfile);
                    } catch (Throwable) {
                        try {
                            $stmt = $this->db->prepare("UPDATE `student_profiles` SET `cv_storage_path` = ? WHERE `user_id` = ?");
                            $stmt->execute([$quarantineFilename, $userId]);
                        } catch (Throwable) {
                        }
                    }

                    throw new RuntimeException(
                        "Lỗi nghiêm trọng: Lưu trữ dữ liệu thất bại và không thể hoàn tác tệp CV ({$quarantineFilename}) về ({$activeStoragePath}). " .
                        "Tệp hiện được lưu giữ an toàn tại [{$quarantineFilename}]. Lỗi gốc: " . $e->getMessage(),
                        0,
                        $e
                    );
                }
            }
            throw $e;
        }

        // Database updated successfully; permanently purge the quarantined file
        if ($quarantineFilename !== null) {
            $purged = $this->storageService->purgeQuarantinedFile($quarantineFilename);
            if (!$purged) {
                // Purge failed! The physical private CV file still exists on disk.
                // Do not report successful DELETE. Restore the original file and metadata to maintain consistency.
                $restored = $this->storageService->restoreQuarantinedFile($quarantineFilename, $activeStoragePath);
                $restoredStoragePath = $restored ? $activeStoragePath : $quarantineFilename;

                try {
                    $rollbackProfile = Profile::fromArray($existing);
                    $rollbackProfile->setCvStoragePath($restoredStoragePath);
                    $this->profileRepository->upsert($rollbackProfile);
                } catch (Throwable) {
                    try {
                        $stmt = $this->db->prepare("UPDATE `student_profiles` SET `cv_storage_path` = ? WHERE `user_id` = ?");
                        $stmt->execute([$restoredStoragePath, $userId]);
                    } catch (Throwable) {
                    }
                }

                throw new RuntimeException(
                    "Không thể xóa hoàn toàn tệp tin CV vật lý khỏi hệ thống lưu trữ an toàn. " .
                    "Hồ sơ và tệp CV đã được hoàn tác để duy trì tính nhất quán."
                );
            }
        }
    }

    private function enforceStudentRole(array $user): void
    {
        $role = $user['role'] ?? '';
        if ($role !== 'student' && $role !== 'developer') {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền quản lý CV cá nhân.");
        }
    }
}
