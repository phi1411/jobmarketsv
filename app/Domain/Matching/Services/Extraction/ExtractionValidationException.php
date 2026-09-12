<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services\Extraction;

use RuntimeException;

final class ExtractionValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Dữ liệu trích xuất từ AI không hợp lệ',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
