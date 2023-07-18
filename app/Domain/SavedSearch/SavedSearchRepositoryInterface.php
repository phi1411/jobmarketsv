<?php

namespace JobMarket\Domain\SavedSearch;

interface SavedSearchRepositoryInterface
{
    public function getAll(): array;
    public function create(SavedSearch $search): void;
    public function delete(string $id): void;
}