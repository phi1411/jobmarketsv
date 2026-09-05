<?php

namespace JobMarket\Exceptions;

class AuthorizationException extends AppException
{
    public function __construct(string $message = "Bạn không có quyền thực hiện hành động này")
    {
        parent::__construct($message, 403);
    }
}
