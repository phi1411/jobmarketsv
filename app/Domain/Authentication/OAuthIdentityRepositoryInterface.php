<?php

namespace JobMarket\Domain\Authentication;

interface OAuthIdentityRepositoryInterface
{
    /**
     * Find identity entity by provider and provider subject (e.g. 'google', $sub).
     */
    public function findIdentity(string $provider, string $providerSubject): ?OAuthIdentity;

    /**
     * Find raw identity array by provider and provider subject.
     */
    public function findIdentityRecord(string $provider, string $providerSubject): ?array;

    /**
     * Find raw identity array by user id and provider.
     */
    public function findIdentityByUserId(string $userId, string $provider): ?array;

    /**
     * Create an OAuth identity record.
     */
    public function createIdentity(OAuthIdentity $identity): void;

    /**
     * Find existing local user by email for collision check.
     */
    public function findLocalUserByEmail(string $email): ?array;

    /**
     * Atomically create user + identity (+ company skeleton pending if role === 'company') in a single transaction.
     * Rollback all new data if any step fails.
     *
     * @param array $userData ['name' => string, 'email' => string, 'role' => string, 'company_name' => ?string]
     * @param array $identityData ['provider' => string, 'provider_subject' => string, 'email_at_link' => ?string]
     * @return array ['user' => array, 'identity' => array, 'company' => ?array]
     */
    public function createOAuthUserWithIdentity(array $userData, array $identityData): array;
}