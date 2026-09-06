<?php

namespace JobMarket\Domain\Assistant\Exceptions;

use Throwable;

class GeminiBlockedContentException extends GeminiException
{
    public function __construct(
        string $message = "Yêu cầu không thể xử lý do vi phạm chính sách an toàn nội dung. Vui lòng đặt câu hỏi phù hợp hơn.",
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
