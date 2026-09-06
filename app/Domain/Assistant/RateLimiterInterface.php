<?php

namespace JobMarket\Domain\Assistant;

interface RateLimiterInterface
{
    /**
     * Atomically check and consume an attempt for the given key.
     * Uses exclusive locking across read/expiry/check/increment/write.
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return RateLimitResult
     */
    public function consume(string $key, int $maxAttempts, int $decaySeconds = 60): RateLimitResult;

    /**
     * Determine if the given key has exceeded too many attempts.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool;

    /**
     * Increment the counter for a given key for a decay duration in seconds.
     *
     * @return int The current attempt count
     */
    public function hit(string $key, int $decaySeconds = 60): int;

    /**
     * Get the number of seconds until the key is accessible again.
     */
    public function availableIn(string $key): int;

    /**
     * Reset the number of attempts for the given key.
     */
    public function clear(string $key): void;
}
