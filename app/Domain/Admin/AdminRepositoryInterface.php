<?php

namespace JobMarket\Domain\Admin;

use JobMarket\Support\Pagination;

interface AdminRepositoryInterface
{
    public function getUsers(array $filters = [], ?Pagination $pagination = null): array;
    public function countUsers(array $filters = []): int;
    public function getUserById(string $id): ?array;
    public function updateUserStatus(string $id, string $status): void;
    public function countActiveAdmins(): int;

    public function getCompanies(array $filters = [], ?Pagination $pagination = null): array;
    public function countCompanies(array $filters = []): int;
    public function getCompanyById(string $id): ?array;
    public function updateCompanyVerification(string $id, string $verificationStatus, ?string $rejectionReason): void;

    public function getJobs(array $filters = [], ?Pagination $pagination = null): array;
    public function countJobs(array $filters = []): int;
    public function getJobById(string $id): ?array;
    public function updateJobModeration(string $id, string $status, ?string $rejectionReason): void;

    public function logAudit(string $actorId, string $action, string $targetType, string $targetId, ?array $metadata = null): void;
    public function getAuditLogs(array $filters = [], ?Pagination $pagination = null): array;
    public function countAuditLogs(array $filters = []): int;
}