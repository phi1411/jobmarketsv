<?php

namespace JobMarket\Domain\CvBuilder;

use JobMarket\Exceptions\ValidationException;

class CvSchema
{
    public const SECTIONS = [
        'personal', 'summary', 'education', 'skills', 'projects', 'experience',
        'activities', 'certifications', 'awards', 'languages', 'interests', 'custom_sections'
    ];

    public const DEFAULT_SECTION_ORDER = [
        'personal', 'summary', 'education', 'skills', 'projects', 'experience',
        'activities', 'certifications', 'awards', 'languages', 'interests', 'custom_sections'
    ];

    private const PERSONAL_FIELDS = [
        'full_name' => 150,
        'job_title' => 150,
        'email' => 180,
        'phone' => 30,
        'address' => 255,
        'website' => 500,
        'linkedin' => 500,
        'date_of_birth' => 20,
    ];

    private const LIST_SCHEMAS = [
        'education' => [
            'school' => 180, 'degree' => 150, 'major' => 150, 'start_date' => 20,
            'end_date' => 20, 'gpa' => 30, 'description' => 3000,
        ],
        'experience' => [
            'organization' => 180, 'position' => 150, 'start_date' => 20,
            'end_date' => 20, 'description' => 4000,
        ],
        'projects' => [
            'name' => 180, 'role' => 150, 'url' => 500, 'start_date' => 20,
            'end_date' => 20, 'description' => 4000, 'technologies' => 500,
        ],
        'skills' => ['name' => 100, 'level' => 50],
        'activities' => [
            'organization' => 180, 'role' => 150, 'start_date' => 20,
            'end_date' => 20, 'description' => 3000,
        ],
        'certifications' => ['name' => 180, 'issuer' => 180, 'issued_date' => 20, 'url' => 500],
        'awards' => ['name' => 180, 'issuer' => 180, 'issued_date' => 20, 'description' => 2000],
        'languages' => ['name' => 100, 'level' => 100],
    ];

    public static function emptyContent(array $user = []): array
    {
        return [
            'personal' => [
                'full_name' => trim((string)($user['name'] ?? '')),
                'job_title' => '',
                'email' => trim((string)($user['email'] ?? '')),
                'phone' => '',
                'address' => '',
                'website' => '',
                'linkedin' => '',
                'date_of_birth' => '',
            ],
            'summary' => '',
            'education' => [],
            'skills' => [],
            'projects' => [],
            'experience' => [],
            'activities' => [],
            'certifications' => [],
            'awards' => [],
            'languages' => [],
            'interests' => '',
            'custom_sections' => [],
        ];
    }

    public static function normalizeContent(mixed $raw, array $base = []): array
    {
        if (!is_array($raw)) {
            throw new ValidationException(['content' => ['Nội dung CV phải là một object JSON.']]);
        }

        $unknown = array_diff(array_keys($raw), self::SECTIONS);
        if ($unknown !== []) {
            throw new ValidationException(['content' => ['Mục CV không hỗ trợ: ' . implode(', ', $unknown) . '.']]);
        }

        $content = $base !== [] ? $base : self::emptyContent();
        $errors = [];

        if (array_key_exists('personal', $raw)) {
            if (!is_array($raw['personal'])) {
                $errors['content.personal'][] = 'Thông tin cá nhân phải là một object.';
            } else {
                $personal = is_array($content['personal'] ?? null) ? $content['personal'] : [];
                foreach ($raw['personal'] as $key => $value) {
                    if (!array_key_exists($key, self::PERSONAL_FIELDS)) {
                        $errors['content.personal'][] = "Trường {$key} không được hỗ trợ.";
                        continue;
                    }
                    $normalized = self::normalizeString($value, self::PERSONAL_FIELDS[$key], "content.personal.{$key}", $errors);
                    if (in_array($key, ['website', 'linkedin'], true) && $normalized !== '' && !self::isHttpUrl($normalized)) {
                        $errors["content.personal.{$key}"][] = 'Đường dẫn phải bắt đầu bằng http:// hoặc https://.';
                    }
                    if ($key === 'email' && $normalized !== '' && !filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                        $errors['content.personal.email'][] = 'Email không đúng định dạng.';
                    }
                    $personal[$key] = $normalized;
                }
                $content['personal'] = $personal;
            }
        }

        foreach (['summary' => 4000, 'interests' => 1000] as $field => $max) {
            if (array_key_exists($field, $raw)) {
                $content[$field] = self::normalizeString($raw[$field], $max, "content.{$field}", $errors);
            }
        }

        foreach (self::LIST_SCHEMAS as $section => $schema) {
            if (!array_key_exists($section, $raw)) {
                continue;
            }
            $content[$section] = self::normalizeList($raw[$section], $section, $schema, $errors);
        }

        if (array_key_exists('custom_sections', $raw)) {
            $content['custom_sections'] = self::normalizeCustomSections($raw['custom_sections'], $errors);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $content;
    }

    public static function normalizeStyle(mixed $raw, array $base = []): array
    {
        if (!is_array($raw)) {
            throw new ValidationException(['style' => ['Cấu hình trình bày phải là một object JSON.']]);
        }
        $style = array_merge([
            'accent_color' => '#0f766e',
            'font_family' => 'DejaVu Sans',
            'font_size' => 10,
            'line_height' => 1.45,
            'paper_size' => 'A4',
        ], $base);
        $allowed = array_keys($style);
        $unknown = array_diff(array_keys($raw), $allowed);
        if ($unknown !== []) {
            throw new ValidationException(['style' => ['Tùy chọn không hỗ trợ: ' . implode(', ', $unknown) . '.']]);
        }

        $errors = [];
        if (array_key_exists('accent_color', $raw)) {
            $color = strtolower(trim((string)$raw['accent_color']));
            if (!preg_match('/^#[0-9a-f]{6}$/', $color)) {
                $errors['style.accent_color'][] = 'Màu nhấn phải có dạng #RRGGBB.';
            } else {
                $style['accent_color'] = $color;
            }
        }
        if (array_key_exists('font_family', $raw)) {
            $font = trim((string)$raw['font_family']);
            if (!in_array($font, ['DejaVu Sans', 'Arial', 'Times New Roman'], true)) {
                $errors['style.font_family'][] = 'Phông chữ không được hỗ trợ.';
            } else {
                $style['font_family'] = $font;
            }
        }
        if (array_key_exists('font_size', $raw)) {
            $size = filter_var($raw['font_size'], FILTER_VALIDATE_INT);
            if ($size === false || $size < 9 || $size > 14) {
                $errors['style.font_size'][] = 'Cỡ chữ phải từ 9 đến 14.';
            } else {
                $style['font_size'] = $size;
            }
        }
        if (array_key_exists('line_height', $raw)) {
            $lineHeight = filter_var($raw['line_height'], FILTER_VALIDATE_FLOAT);
            if ($lineHeight === false || $lineHeight < 1.1 || $lineHeight > 2.0) {
                $errors['style.line_height'][] = 'Giãn dòng phải từ 1.1 đến 2.0.';
            } else {
                $style['line_height'] = round((float)$lineHeight, 2);
            }
        }
        if (array_key_exists('paper_size', $raw) && strtoupper((string)$raw['paper_size']) !== 'A4') {
            $errors['style.paper_size'][] = 'Phiên bản hiện tại chỉ hỗ trợ khổ A4.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        return $style;
    }

    public static function normalizeSectionOrder(mixed $raw): array
    {
        if (!is_array($raw)) {
            throw new ValidationException(['section_order' => ['Thứ tự mục phải là một mảng.']]);
        }
        $values = array_values(array_map('strval', $raw));
        if (count($values) !== count(array_unique($values)) || array_diff($values, self::SECTIONS) !== []) {
            throw new ValidationException(['section_order' => ['Thứ tự mục chứa giá trị trùng hoặc không hợp lệ.']]);
        }
        return array_values(array_merge($values, array_diff(self::DEFAULT_SECTION_ORDER, $values)));
    }

    public static function normalizeHiddenSections(mixed $raw): array
    {
        if (!is_array($raw)) {
            throw new ValidationException(['hidden_sections' => ['Danh sách mục ẩn phải là một mảng.']]);
        }
        $values = array_values(array_unique(array_map('strval', $raw)));
        if (array_diff($values, self::SECTIONS) !== []) {
            throw new ValidationException(['hidden_sections' => ['Danh sách mục ẩn chứa giá trị không hợp lệ.']]);
        }
        return $values;
    }

    public static function completionPercent(array $content): int
    {
        $personal = $content['personal'] ?? [];
        $checks = [
            !empty($personal['full_name']),
            !empty($personal['email']) || !empty($personal['phone']),
            !empty($personal['job_title']),
            !empty($content['summary']),
            !empty($content['education']),
            !empty($content['skills']),
            !empty($content['projects']) || !empty($content['experience']) || !empty($content['activities']),
            !empty($content['languages']) || !empty($content['certifications']) || !empty($content['awards']),
        ];
        return (int)round((count(array_filter($checks)) / count($checks)) * 100);
    }

    private static function normalizeList(mixed $raw, string $section, array $schema, array &$errors): array
    {
        if (!is_array($raw) || !array_is_list($raw)) {
            $errors["content.{$section}"][] = 'Dữ liệu phải là một mảng.';
            return [];
        }
        if (count($raw) > 30) {
            $errors["content.{$section}"][] = 'Mỗi mục chỉ được tối đa 30 bản ghi.';
            return [];
        }

        $result = [];
        foreach ($raw as $index => $item) {
            if (!is_array($item)) {
                $errors["content.{$section}.{$index}"][] = 'Bản ghi phải là một object.';
                continue;
            }
            $allowed = array_merge(array_keys($schema), ['current']);
            $unknown = array_diff(array_keys($item), $allowed);
            if ($unknown !== []) {
                $errors["content.{$section}.{$index}"][] = 'Trường không hỗ trợ: ' . implode(', ', $unknown) . '.';
            }
            $normalized = [];
            foreach ($schema as $field => $max) {
                $value = self::normalizeString($item[$field] ?? '', $max, "content.{$section}.{$index}.{$field}", $errors);
                if ($field === 'url' && $value !== '' && !self::isHttpUrl($value)) {
                    $errors["content.{$section}.{$index}.url"][] = 'Đường dẫn phải bắt đầu bằng http:// hoặc https://.';
                }
                if (str_ends_with($field, '_date') && $value !== '' && !preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', $value)) {
                    $errors["content.{$section}.{$index}.{$field}"][] = 'Ngày phải có dạng YYYY, YYYY-MM hoặc YYYY-MM-DD.';
                }
                $normalized[$field] = $value;
            }
            if (array_key_exists('current', $item)) {
                $normalized['current'] = filter_var($item['current'], FILTER_VALIDATE_BOOL);
            }
            $result[] = $normalized;
        }
        return $result;
    }

    private static function normalizeCustomSections(mixed $raw, array &$errors): array
    {
        if (!is_array($raw) || !array_is_list($raw)) {
            $errors['content.custom_sections'][] = 'Mục tùy chỉnh phải là một mảng.';
            return [];
        }
        if (count($raw) > 10) {
            $errors['content.custom_sections'][] = 'Chỉ được tạo tối đa 10 mục tùy chỉnh.';
            return [];
        }
        $result = [];
        foreach ($raw as $index => $section) {
            if (!is_array($section)) {
                $errors["content.custom_sections.{$index}"][] = 'Mục tùy chỉnh phải là object.';
                continue;
            }
            $title = self::normalizeString($section['title'] ?? '', 120, "content.custom_sections.{$index}.title", $errors);
            $items = self::normalizeList(
                $section['items'] ?? [],
                "custom_sections.{$index}.items",
                ['title' => 180, 'subtitle' => 180, 'date' => 100, 'description' => 3000],
                $errors
            );
            $result[] = [
                'id' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($section['id'] ?? 'custom-' . ($index + 1))),
                'title' => $title,
                'items' => $items,
            ];
        }
        return $result;
    }

    private static function normalizeString(mixed $value, int $max, string $path, array &$errors): string
    {
        if ($value === null) {
            return '';
        }
        if (!is_scalar($value)) {
            $errors[$path][] = 'Giá trị phải là chuỗi.';
            return '';
        }
        $value = trim((string)$value);
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($length > $max) {
            $errors[$path][] = "Nội dung không được vượt quá {$max} ký tự.";
        }
        return $value;
    }

    private static function isHttpUrl(string $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }
}
