<?php

namespace JobMarket\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message = "Tài nguyên yêu cầu không tồn tại")
    {
        parent::__construct($message, 404);
    }
}
