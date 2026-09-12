<?php

namespace JobMarket\Domain\Assistant;

class GeminiResponse
{
    public function __construct(
        public readonly string $text,
        public readonly bool $isBlocked = false,
        public readonly ?string $finishReason = null,
        public readonly ?string $safetyNotice = null,
        public readonly ?array $usageMetadata = null
    ) {
    }
}
