<?php

namespace JobMarket\Domain\CvBuilder;

/**
 * Converts the shared student profile into a one-time CV content snapshot.
 * The generated CV is intentionally detached from the profile after creation.
 */
class ProfileCvContentMapper
{
    public function map(array $profile, array $user = []): array
    {
        $content = CvSchema::emptyContent($user);
        $content['personal'] = array_merge($content['personal'], [
            'full_name' => $this->text($profile['full_name'] ?? ($profile['user_account_name'] ?? ($user['name'] ?? '')), 150),
            'email' => $this->text($profile['email'] ?? ($user['email'] ?? ''), 180),
            'phone' => $this->text($profile['phone'] ?? '', 30),
            'date_of_birth' => $this->text($profile['date_of_birth'] ?? '', 20),
        ]);
        $content['summary'] = $this->text($profile['bio'] ?? '', 4000);
        $content['education'] = $this->education($profile);
        $content['skills'] = $this->skills($profile['skills'] ?? null);
        $content['experience'] = $this->experience($profile['work_experience'] ?? null);
        $content['certifications'] = $this->certifications($profile['certificates'] ?? null);

        return CvSchema::normalizeContent($content);
    }

    public function populatedSections(array $content): array
    {
        $labels = [
            'personal' => 'Thông tin cá nhân',
            'summary' => 'Giới thiệu',
            'education' => 'Học vấn',
            'skills' => 'Kỹ năng',
            'experience' => 'Kinh nghiệm',
            'certifications' => 'Chứng chỉ',
        ];
        $sections = [];

        foreach ($labels as $key => $label) {
            $value = $content[$key] ?? null;
            if ($key === 'personal') {
                $value = array_filter(is_array($value) ? $value : [], fn(mixed $item): bool => trim((string)$item) !== '');
            }
            if (!empty($value)) {
                $sections[] = $label;
            }
        }

        return $sections;
    }

    private function education(array $profile): array
    {
        $raw = $this->decode($profile['education'] ?? null);
        $school = $this->text($profile['university'] ?? '', 180);
        $major = $this->text($profile['major'] ?? '', 150);
        $degree = '';
        $endDate = '';
        $description = '';

        if (is_array($raw) && !array_is_list($raw)) {
            $school = $this->text($raw['university'] ?? $school, 180);
            $major = $this->text($raw['major'] ?? $major, 150);
            $degree = $this->text($raw['degree'] ?? '', 150);
            $endDate = $this->cvDate($raw['grad_year'] ?? '');
            $description = $this->text($raw['description'] ?? '', 3000);
        } elseif (is_string($raw)) {
            $description = $this->text($raw, 3000);
        }

        if ($school === '' && $major === '' && $degree === '' && $endDate === '' && $description === '') {
            return [];
        }

        return [[
            'school' => $school,
            'degree' => $degree,
            'major' => $major,
            'start_date' => '',
            'end_date' => $endDate,
            'gpa' => '',
            'description' => $description,
        ]];
    }

    private function skills(mixed $raw): array
    {
        $decoded = $this->decode($raw);
        $names = [];

        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $name = is_array($item) ? ($item['name'] ?? '') : $item;
                $name = $this->text($name, 100);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        } elseif (is_string($decoded)) {
            $names = preg_split('/[,;\r\n]+/u', $decoded) ?: [];
        }

        $names = array_slice(array_values(array_unique(array_filter(array_map(
            fn(mixed $name): string => $this->text($name, 100),
            $names
        )))), 0, 30);

        return array_map(fn(string $name): array => ['name' => $name, 'level' => ''], $names);
    }

    private function experience(mixed $raw): array
    {
        $decoded = $this->decode($raw);
        if (is_string($decoded)) {
            $decoded = [['description' => $decoded]];
        }
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach (array_slice($decoded, 0, 30) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $duration = $this->text($item['duration'] ?? '', 100);
            $description = $this->text($item['description'] ?? '', 4000);
            if ($duration !== '') {
                $description = $this->text('Thời gian: ' . $duration . ($description !== '' ? "\n" . $description : ''), 4000);
            }
            $row = [
                'organization' => $this->text($item['company'] ?? ($item['organization'] ?? ''), 180),
                'position' => $this->text($item['title'] ?? ($item['position'] ?? ''), 150),
                'start_date' => $this->cvDate($item['start_date'] ?? ''),
                'end_date' => $this->cvDate($item['end_date'] ?? ''),
                'description' => $description,
                'current' => $this->isCurrent($item['current'] ?? null, $duration),
            ];
            if (implode('', array_map(fn(mixed $value): string => is_bool($value) ? '' : (string)$value, $row)) !== '') {
                $items[] = $row;
            }
        }

        return $items;
    }

    private function certifications(mixed $raw): array
    {
        $decoded = $this->decode($raw);
        if (is_string($decoded)) {
            $decoded = preg_split('/[,;\r\n]+/u', $decoded) ?: [];
        }
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach (array_slice($decoded, 0, 30) as $item) {
            $item = is_array($item) ? $item : ['name' => $item];
            $name = $this->text($item['name'] ?? '', 180);
            if ($name === '') {
                continue;
            }
            $year = $this->text($item['year'] ?? ($item['issued_date'] ?? ''), 180);
            $issuedDate = $this->cvDate($year);
            $items[] = [
                'name' => $name,
                'issuer' => $issuedDate === '' ? $year : $this->text($item['issuer'] ?? '', 180),
                'issued_date' => $issuedDate,
                'url' => '',
            ];
        }

        return $items;
    }

    private function decode(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }
        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $trimmed;
    }

    private function cvDate(mixed $value): string
    {
        $value = trim((string)$value);
        if (!preg_match('/^(\d{4})(?:-(0[1-9]|1[0-2])(?:-(0[1-9]|[12]\d|3[01]))?)?$/', $value, $parts)) {
            return '';
        }
        if (isset($parts[3]) && $parts[3] !== '' && !checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1])) {
            return '';
        }
        return $value;
    }

    private function isCurrent(mixed $value, string $duration): bool
    {
        if (is_bool($value) || in_array($value, [0, 1, '0', '1'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOL);
        }
        return preg_match('/\b(hiện tại|nay|present|current)\b/ui', $duration) === 1;
    }

    private function text(mixed $value, int $limit): string
    {
        if (!is_scalar($value)) {
            return '';
        }
        $value = trim(strip_tags((string)$value));
        return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
    }
}
