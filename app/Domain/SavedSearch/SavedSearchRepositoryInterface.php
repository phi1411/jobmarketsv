<?php

namespace JobMarket\Domain\SavedSearch;

use JobMarket\Support\Pagination;

interface SavedSearchRepositoryInterface
{
    public function create(SavedSearch $search): void;
    public function findById(string $id): ?array;
    public function getByUser(string $userId, ?Pagination $pagination = null): array;
    public function countByUser(string $userId): int;
    public function update(SavedSearch $search): void;
    public function delete(string $id): void;
}