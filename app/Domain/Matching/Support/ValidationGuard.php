<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Support;

use BackedEnum;
use JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException;

final class ValidationGuard
{
    /**
     * Forbidden PII & protected attribute keys across all matching contracts.
     */
    public const FORBIDDEN_PII_KEYS = [
        'full_name',
        'student_name',
        'email',
        'student_email',
        'phone',
        'student_phone',
        'date_of_birth',
        'dob',
        'birth_date',
        'gender',
        'sex',
        'age',
        'address',
        'exact_address',
        'home_address',
        'cv_storage_path',
        'cv_original_name',
        'cv_url',
        'employer_note',
    ];

    /**
     * Recursively verifies that no PII or protected fields exist in the payload.
     *
     * @param array<string, mixed> $data
     */
    public static function assertNoPii(array $data, string $context = ''): void
    {
        foreach ($data as $key => $value) {
            $keyLower = strtolower((string)$key);
            if (in_array($keyLower, self::FORBIDDEN_PII_KEYS, true)) {
                $location = $context !== '' ? "{$context}.{$key}" : (string)$key;
                throw new MatchingContractValidationException(
                    [$location => "Trường '{$location}' là PII/protected attribute bị cấm trong matching contract."],
                    "Phát hiện trường dữ liệu định danh (PII) hoặc thuộc tính được bảo vệ không hợp lệ"
                );
            }
            if (is_array($value)) {
                $nextContext = $context !== '' ? "{$context}.{$key}" : (string)$key;
                self::assertNoPii($value, $nextContext);
            }
        }
    }

    /**
     * Enforces strict keys in an array (no additional/unknown properties).
     *
     * @param array<string, mixed> $data
     * @param list<string> $allowedKeys
     */
    public static function assertStrictKeys(array $data, array $allowedKeys, string $context = ''): void
    {
        $allowedMap = array_flip($allowedKeys);
        $unknown = [];

        foreach (array_keys($data) as $key) {
            $kStr = (string)$key;
            if (!isset($allowedMap[$kStr])) {
                $unknown[] = $context !== '' ? "{$context}.{$kStr}" : $kStr;
            }
        }

        if (!empty($unknown)) {
            $fields = implode(', ', $unknown);
            throw new MatchingContractValidationException(
                ['unknown_fields' => "Các trường không được phép: {$fields}"],
                "Phát hiện trường bổ sung ngoài quy định (strict contract violation): {$fields}"
            );
        }
    }

    /**
     * Validates that confidence is numeric (strict float or int) between 0.0 and 1.0.
     */
    public static function assertConfidence(mixed $value, string $field): float
    {
        if (!is_int($value) && !is_float($value)) {
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' phải là số thực (float), không chấp nhận chuỗi ép kiểu."],
                "Sai kiểu dữ liệu cho '{$field}'"
            );
        }

        $fVal = (float)$value;
        if ($fVal < 0.0 || $fVal > 1.0) {
            throw new MatchingContractValidationException(
                [$field => "Confidence '{$field}' phải nằm trong khoảng từ 0.0 đến 1.0. Giá trị nhận được: {$fVal}"],
                "Confidence vượt giới hạn cho phép [0.0, 1.0]"
            );
        }

        return $fVal;
    }

    /**
     * Validates that score is numeric (strict float or int) between 0.0 and 100.0.
     */
    public static function assertScore(mixed $value, string $field): float
    {
        if (!is_int($value) && !is_float($value)) {
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' phải là số (int hoặc float), không chấp nhận chuỗi ép kiểu."],
                "Sai kiểu dữ liệu cho '{$field}'"
            );
        }

        $fVal = (float)$value;
        if ($fVal < 0.0 || $fVal > 100.0) {
            throw new MatchingContractValidationException(
                [$field => "Score '{$field}' phải nằm trong khoảng từ 0.0 đến 100.0. Giá trị nhận được: {$fVal}"],
                "Score vượt giới hạn cho phép [0, 100]"
            );
        }

        return $fVal;
    }

    /**
     * Validates string type and length constraint.
     */
    public static function assertStringLength(
        mixed $value,
        string $field,
        int $max = 255,
        bool $allowNull = false
    ): ?string {
        if ($value === null) {
            if ($allowNull) {
                return null;
            }
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' không được để trống (null)."],
                "Thiếu trường bắt buộc '{$field}'"
            );
        }

        if (!is_string($value)) {
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' phải là chuỗi (string), không chấp nhận kiểu khác."],
                "Sai kiểu dữ liệu cho '{$field}'"
            );
        }

        if (!$allowNull && trim($value) === '') {
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' không được là chuỗi rỗng hoặc chỉ chứa khoảng trắng."],
                "Trường bắt buộc '{$field}' không được rỗng"
            );
        }

        if (mb_strlen($value) > $max) {
            throw new MatchingContractValidationException(
                [$field => "Độ dài trường '{$field}' không được vượt quá {$max} ký tự."],
                "Độ dài chuỗi vượt giới hạn cho '{$field}'"
            );
        }

        return $value;
    }

    /**
     * Rejects PII placeholders ([REDACTED_*]) inside semantic/canonical fields.
     */
    public static function assertNoPlaceholder(mixed $value, string $field): void
    {
        if (is_string($value) && preg_match('/\[REDACTED_[A-Z]+\]/', $value)) {
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' không được chứa placeholder PII [REDACTED_*]. Giá trị nhận được: '{$value}'"],
                "Placeholder PII không hợp lệ trong matching contract"
            );
        }
    }

    /**
     * Validates array type and item count constraint.
     *
     * @return array<mixed>|null
     */
    public static function assertArrayLimit(
        mixed $value,
        string $field,
        int $maxItems = 100,
        bool $allowNull = false
    ): ?array {
        if ($value === null) {
            if ($allowNull) {
                return null;
            }
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' không được là null."],
                "Thiếu trường bắt buộc '{$field}'"
            );
        }

        if (!is_array($value)) {
            throw new MatchingContractValidationException(
                [$field => "Trường '{$field}' phải là danh sách (array), không chấp nhận kiểu khác."],
                "Sai kiểu dữ liệu cho '{$field}'"
            );
        }

        if (count($value) > $maxItems) {
            throw new MatchingContractValidationException(
                [$field => "Số lượng phần tử trong '{$field}' không được vượt quá {$maxItems} items."],
                "Số lượng phần tử vượt giới hạn cho '{$field}'"
            );
        }

        return $value;
    }

    /**
     * Resolves and validates a backed enum value or instance.
     *
     * @template T of BackedEnum
     * @param class-string<T> $enumClass
     * @return T
     */
    public static function assertEnum(mixed $value, string $enumClass, string $field): BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        if (!is_string($value)) {
            throw new MatchingContractValidationException(
                [$field => "Giá trị của '{$field}' phải là chuỗi hợp lệ tương ứng với enum {$enumClass}."],
                "Sai kiểu dữ liệu enum cho '{$field}'"
            );
        }

        $case = $enumClass::tryFrom($value);
        if ($case === null) {
            $allowed = implode(', ', $enumClass::values());
            throw new MatchingContractValidationException(
                [$field => "Giá trị '{$value}' không hợp lệ cho enum '{$field}'. Các giá trị cho phép: {$allowed}."],
                "Giá trị enum không hợp lệ cho '{$field}'"
            );
        }

        return $case;
    }
}
