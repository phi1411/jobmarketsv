<?php

namespace JobMarket\Domain\Profile;

use JobMarket\Support\Pagination;

interface ProfileRepositoryInterface
{
    public function findByUserId(string $userId): ?array;
    public function findById(string $id): ?array;
    public function upsert(Profile $profile): void;
    public function updateCvMetadata(string $userId, ?array $cvData): void;
    public function searchPublic(array $filters = [], ?Pagination $pagination = null): array;
    public function countPublic(array $filters = []): int;
}