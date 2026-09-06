<?php

namespace JobMarket\Domain\Assistant\Exceptions;

use Throwable;

class GeminiServiceException extends GeminiException
{
    public function __construct(
        string $message = "Hệ thống trợ lý AI gặp sự cố khi xử lý phản hồi. Vui lòng thử lại sau.",
        int $code = 503,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
