<?php

namespace JobMarket\Domain\Authentication;

interface OAuthStateStoreInterface
{
    /**
     * Save state data with a short TTL (seconds).
     *
     * @param string $state Unique state token
     * @param array $data Stored payload ['nonce' => string, 'role' => string, 'return_url' => ?string, 'created_at' => int, 'expires_at' => int]
     * @param int $ttl Time to live in seconds (default 300 = 5 minutes)
     */
    public function save(string $state, array $data, int $ttl = 300): void;

    /**
     * Retrieve and immediately delete (consume) state data.
     * Returns null if state is invalid, expired, or already consumed.
     *
     * @param string $state
     * @return array|null
     */
    public function consume(string $state): ?array;

    /**
     * Clean up expired state entries.
     */
    public function clearExpired(): void;
}
