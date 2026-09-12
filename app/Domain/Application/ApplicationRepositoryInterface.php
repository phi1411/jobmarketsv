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
    public function updateStatus(string $id, string $status, ?string $studentMessage = null, ?string $actorId = null, string $actorRole = "system", ?string $historyNote = null): void;
    public function withdraw(string $id, ?string $actorId = null, string $actorRole = "student"): void;
    public function getStatusHistory(string $applicationId): array;
    public function updateConsent(string $id, bool $consent, ?string $revokedAt = null): void;
}
