<?php

namespace JobMarket\Domain\CvBuilder;

interface OnlineCvRepositoryInterface
{
    public function listByUser(string $userId): array;

    public function findOwned(string $id, string $userId): ?array;

    public function findPublicBySlug(string $slug): ?array;

    public function countByUser(string $userId): int;

    public function create(array $cv): void;

    public function update(string $id, string $userId, array $changes, ?int $expectedVersion = null): bool;

    public function softDelete(string $id, string $userId): bool;

    public function clearPrimary(string $userId, ?string $exceptId = null): void;

    public function touchExported(string $id): void;
}
