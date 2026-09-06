<?php

namespace JobMarket\Domain\Assistant\Exceptions;

use RuntimeException;
use Throwable;

class GeminiException extends RuntimeException
{
    public function __construct(
        string $message = "Lỗi dịch vụ trợ lý AI",
        int $code = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
