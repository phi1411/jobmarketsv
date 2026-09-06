<?php

namespace JobMarket\Domain\Assistant\Exceptions;

use Throwable;

class GeminiRateLimitException extends GeminiException
{
    public function __construct(
        string $message = "Hệ thống trợ lý AI đang quá tải lượt yêu cầu. Vui lòng thử lại sau ít phút.",
        int $code = 429,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
