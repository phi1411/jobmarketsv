<?php

namespace JobMarket\Domain\Job;

use JobMarket\Support\Pagination;

interface JobRepositoryInterface
{
    public function getAll(): array;
    public function create(Job $job): void;
    public function findById(string $id): array;
    public function update(Job $job): void;
    public function delete(string $id): void;
    public function search(array $filters = [], ?Pagination $pagination = null): array;
    public function count(array $filters = []): int;
    public function findByCompany(string $companyId, array $filters = [], ?Pagination $pagination = null): array;
    public function countByCompany(string $companyId, array $filters = []): int;
    public function close(string $id): void;
}