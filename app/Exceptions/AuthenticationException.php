<?php

namespace JobMarket\Exceptions;

class AuthenticationException extends AppException
{
    public function __construct(string $message = "Chưa xác thực hoặc phiên đăng nhập không hợp lệ")
    {
        parent::__construct($message, 401);
    }
}
