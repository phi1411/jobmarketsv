<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Favorite\Favorite;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Infrastructure\FavoriteRepository;
use JobMarket\Infrastructure\JobRepository;
use JobMarket\Support\Pagination;

class FavoriteService
{
    private FavoriteRepository $favoriteRepo;
    private JobRepository $jobRepo;

    public function __construct(?FavoriteRepository $favoriteRepo = null, ?JobRepository $jobRepo = null)
    {
        $this->favoriteRepo = $favoriteRepo ?? new FavoriteRepository();
        $this->jobRepo = $jobRepo ?? new JobRepository();
    }

    public function addFavorite(array $user, string $jobId): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền lưu việc làm yêu thích.");
        }

        $userId = $user["id"] ?? "";

        // Verify Job exists and is eligible
        $job = $this->jobRepo->findById($jobId);
        if (!$job || $job["status"] !== "published" || !empty($job["deleted_at"]) || $job["status"] === "closed") {
            throw new ValidationException(["job_id" => ["Không thể lưu việc làm đã đóng, hết hạn hoặc không tồn tại."]]);
        }
        if ($job["status"] === "expired" || (!empty($job["application_deadline"]) && strtotime($job["application_deadline"]) < strtotime(date("Y-m-d")))) {
            throw new ValidationException(["job_id" => ["Không thể lưu việc làm đã hết hạn ứng tuyển."]]);
        }

        $isAlready = $this->favoriteRepo->isFavorited($userId, $jobId);
        if ($isAlready) {
            return [
                "favorited" => true,
                "is_new"    => false,
                "message"   => "Việc làm đã có trong danh sách yêu thích của bạn."
            ];
        }

        $id = $this->favoriteRepo->add($userId, $jobId);

        return [
            "favorited" => true,
            "is_new"    => true,
            "id"        => $id,
            "message"   => "Đã lưu việc làm vào danh sách yêu thích thành công."
        ];
    }

    public function removeFavorite(array $user, string $jobId): void
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền xóa việc làm yêu thích.");
        }

        $userId = $user["id"] ?? "";
        $this->favoriteRepo->remove($userId, $jobId);
    }

    public function getMyFavorites(array $user, ?Pagination $pagination = null): array
    {
        $role = $user["role"] ?? "";
        if ($role !== "student" && $role !== "developer") {
            throw new AuthorizationException("Chỉ tài khoản Sinh viên mới có quyền xem danh sách việc làm yêu thích.");
        }

        $userId = $user["id"] ?? "";
        $rows = $this->favoriteRepo->getByUser($userId, $pagination);
        $total = $this->favoriteRepo->countByUser($userId);

        $items = array_map(function($row) {
            return Favorite::fromArray($row)->toArray();
        }, $rows);

        return [
            "items" => $items,
            "total" => $total
        ];
    }
}
