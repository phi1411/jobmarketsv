<?php

namespace JobMarket\Domain\Assistant;

class RateLimitResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $remaining,
        public readonly int $retryAfter = 0,
        public readonly bool $failedClosed = false
    ) {}
}
