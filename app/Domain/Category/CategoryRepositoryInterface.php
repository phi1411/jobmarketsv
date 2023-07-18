<?php

namespace JobMarket\Domain\Category;

interface CategoryRepositoryInterface
{
    public function getAll(): array;
    public function create(Category $category): void;
    public function getById(string $id): array;
    public function update(Category $category): void;
    public function delete(string $id): void;
}
