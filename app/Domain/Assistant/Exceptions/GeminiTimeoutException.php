<?php

namespace JobMarket\Domain\Assistant\Exceptions;

use Throwable;

class GeminiTimeoutException extends GeminiException
{
    public function __construct(
        string $message = "Hệ thống trợ lý AI đang phản hồi chậm. Vui lòng thử lại sau giây lát.",
        int $code = 504,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
