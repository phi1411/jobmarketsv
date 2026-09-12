<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Exceptions;

use JobMarket\Exceptions\AppException;

class MatchingContractValidationException extends AppException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        array $errors,
        string $message = "Dữ liệu hợp đồng matching không hợp lệ"
    ) {
        parent::__construct($message, 422, $errors);
    }
}
