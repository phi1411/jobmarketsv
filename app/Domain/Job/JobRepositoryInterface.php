<?php

namespace JobMarket\Domain\Job;

interface JobRepositoryInterface
{
    public function getAll(): array;
    public function create(Job $job): void;
    public function findById(string $id): array;
    public function update(Job $job): void;
    public function delete(string $id): void;
}