<?php

namespace Tests\Unit;

use JobMarket\Domain\CvBuilder\ProfileCvContentMapper;
use PHPUnit\Framework\TestCase;

class ProfileCvContentMapperTest extends TestCase
{
    public function test_it_maps_a_saved_student_profile_to_cv_content(): void
    {
        $content = (new ProfileCvContentMapper())->map([
            'full_name' => 'Nguyễn An',
            'email' => 'an@example.test',
            'phone' => '0901234567',
            'university' => 'Đại học Mở',
            'major' => 'Công nghệ thông tin',
            'bio' => 'Sinh viên định hướng phát triển web.',
            'skills' => 'PHP, MySQL, Giao tiếp',
            'education' => json_encode([
                'degree' => 'Cử nhân',
                'grad_year' => '2027',
                'description' => 'GPA 3.4/4',
            ], JSON_UNESCAPED_UNICODE),
            'work_experience' => json_encode([[
                'title' => 'Thực tập sinh',
                'company' => 'Công ty ABC',
                'duration' => '06/2026 - hiện tại',
                'description' => 'Hỗ trợ xây dựng API.',
            ]], JSON_UNESCAPED_UNICODE),
            'certificates' => json_encode([['name' => 'TOEIC 750', 'year' => '2025']], JSON_UNESCAPED_UNICODE),
        ]);

        $this->assertSame('Nguyễn An', $content['personal']['full_name']);
        $this->assertSame('0901234567', $content['personal']['phone']);
        $this->assertSame('Đại học Mở', $content['education'][0]['school']);
        $this->assertSame('Công nghệ thông tin', $content['education'][0]['major']);
        $this->assertSame(['PHP', 'MySQL', 'Giao tiếp'], array_column($content['skills'], 'name'));
        $this->assertSame('Công ty ABC', $content['experience'][0]['organization']);
        $this->assertTrue($content['experience'][0]['current']);
        $this->assertSame('TOEIC 750', $content['certifications'][0]['name']);
    }
}
