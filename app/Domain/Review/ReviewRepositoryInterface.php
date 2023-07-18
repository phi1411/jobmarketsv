<?php

namespace JobMarket\Domain\Review;

interface ReviewRepositoryInterface
{
    public function getAll(): array;
    public function create(Review $review): void;
    public function getById(string $id): array;
    public function update(Review $review): void;
    public function delete(string $id): void;
}