<?php

namespace JobMarket\Domain\Developer;

interface DeveloperRepositoryInterface
{
    public function getAll(): array;
    public function create(Developer $developer): void;
    public function getById(string $id): array;
    public function update(Developer $developer): void;
    public function delete(string $id): void;
}