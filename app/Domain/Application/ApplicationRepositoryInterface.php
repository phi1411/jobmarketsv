<?php

namespace JobMarket\Domain\Application;

interface ApplicationRepositoryInterface
{
    public function getAll(string $id): array;
    public function create(string $id, Application $application): void;
    public function findById(string $id): array;
    public function update(Application $application): void;
    public function delete(string $id): void;
}