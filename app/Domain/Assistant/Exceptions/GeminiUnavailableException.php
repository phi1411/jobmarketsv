<?php

namespace JobMarket\Domain\Assistant\Exceptions;

use Throwable;

class GeminiUnavailableException extends GeminiException
{
    public function __construct(
        string $message = "Tính năng trợ lý AI tạm thời chưa khả dụng. Vui lòng thử lại sau.",
        int $code = 503,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
