<?php

namespace JobMarket\Domain\Application;

use JobMarket\Support\Pagination;

interface ApplicationRepositoryInterface
{
    public function create(Application $application): void;
    public function findById(string $id): ?array;
    public function findByJobAndStudent(string $jobId, string $studentUserId): ?array;
    public function getByStudent(string $studentUserId, array $filters = [], ?Pagination $pagination = null): array;
    public function countByStudent(string $studentUserId, array $filters = []): int;
    public function getByJob(string $jobId, array $filters = [], ?Pagination $pagination = null): array;
    public function countByJob(string $jobId, array $filters = []): int;
    public function getByCompany(string $companyId, array $filters = [], ?Pagination $pagination = null): array;
    public function countByCompany(string $companyId, array $filters = []): int;
    public function updateStatus(string $id, string $status, ?string $employerNote = null): void;
    public function withdraw(string $id): void;
    public function updateConsent(string $id, bool $consent, ?string $revokedAt = null): void;
}