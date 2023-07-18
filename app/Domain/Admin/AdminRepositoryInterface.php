<?php

namespace JobMarket\Domain\Admin;

interface AdminRepositoryInterface
{
    public function getJobs(): array;
    public function getCompanies(): array;
    public function getUsers(): array;
    public function update(Admin $admin): void;
    public function delete(string $id): void;
}