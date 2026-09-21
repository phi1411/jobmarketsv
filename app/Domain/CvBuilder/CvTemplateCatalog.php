<?php

namespace JobMarket\Domain\CvBuilder;

class CvTemplateCatalog
{
    public const DEFAULT_TEMPLATE = 'student-simple';

    public static function all(): array
    {
        return [
            [
                'key' => 'student-simple',
                'name' => 'Sinh viên tối giản',
                'description' => 'Một cột, dễ đọc, ưu tiên học vấn, dự án và kỹ năng.',
                'tags' => ['student', 'simple', 'ats'],
                'ats_friendly' => true,
                'supports_photo' => false,
                'default_accent_color' => '#0f766e',
                'preview_image' => '/assets/images/cv-templates/student-simple.svg',
            ],
            [
                'key' => 'student-modern',
                'name' => 'Sinh viên hiện đại',
                'description' => 'Bố cục hai cột gọn gàng, phù hợp thực tập và việc làm đầu tiên.',
                'tags' => ['student', 'modern'],
                'ats_friendly' => true,
                'supports_photo' => false,
                'default_accent_color' => '#2563eb',
                'preview_image' => '/assets/images/cv-templates/student-modern.svg',
            ],
            [
                'key' => 'ats-classic',
                'name' => 'ATS cổ điển',
                'description' => 'Đen trắng, một cột, tối ưu khả năng đọc của hệ thống ATS.',
                'tags' => ['ats', 'classic', 'simple'],
                'ats_friendly' => true,
                'supports_photo' => false,
                'default_accent_color' => '#111827',
                'preview_image' => '/assets/images/cv-templates/ats-classic.svg',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }
        return null;
    }
}
