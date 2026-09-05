<?php

namespace JobMarket\Exceptions;

class ValidationException extends AppException
{
    public function __construct(array $errors, string $message = "Dữ liệu đầu vào không hợp lệ")
    {
        parent::__construct($message, 422, $errors);
    }
}
