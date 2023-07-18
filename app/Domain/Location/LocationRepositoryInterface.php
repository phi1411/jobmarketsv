<?php

namespace JobMarket\Domain\Location;

interface LocationRepositoryInterface
{
    public function getAll(): array;
    public function create(Location $location): void;
    public function getById(string $id): array;
    public function update(Location $location): void;
    public function delete(string $id): void;
}