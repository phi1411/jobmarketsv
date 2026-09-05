<?php

namespace JobMarket\Domain\Favorite;

use JobMarket\Support\Pagination;

interface FavoriteRepositoryInterface
{
    public function add(string $userId, string $jobId): string;
    public function remove(string $userId, string $jobId): void;
    public function isFavorited(string $userId, string $jobId): bool;
    public function getByUser(string $userId, ?Pagination $pagination = null): array;
    public function countByUser(string $userId): int;
}