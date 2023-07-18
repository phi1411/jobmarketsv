<?php

namespace JobMarket\Domain\Company;

interface CompanyRepositoryInterface
{
    public function getAll(): array;
    public function create(Company $company): void;
    public function getById(string $id): array;
    public function update(Company $company): void;
    public function delete(string $id): void;
}