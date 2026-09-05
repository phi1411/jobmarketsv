<?php

namespace JobMarket\Exceptions;

use Exception;

class AppException extends Exception
{
    protected int $statusCode;
    protected ?array $errors;

    public function __construct(string $message = "Đã có lỗi xảy ra", int $statusCode = 400, ?array $errors = null)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }
}
