<?php

namespace JobMarket\Domain\CvBuilder;

class CvHtmlRenderer
{
    public function render(array $cv, bool $forPdf = false): string
    {
        $content = is_array($cv['content'] ?? null) ? $cv['content'] : [];
        $style = is_array($cv['style'] ?? null) ? $cv['style'] : [];
        $order = is_array($cv['section_order'] ?? null) ? $cv['section_order'] : CvSchema::DEFAULT_SECTION_ORDER;
        $hidden = is_array($cv['hidden_sections'] ?? null) ? $cv['hidden_sections'] : [];
        $language = ($cv['language'] ?? 'vi') === 'en' ? 'en' : 'vi';
        $template = in_array($cv['template_key'] ?? '', CvTemplateCatalog::keys(), true)
            ? $cv['template_key']
            : CvTemplateCatalog::DEFAULT_TEMPLATE;

        $accent = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($style['accent_color'] ?? ''))
            ? strtolower($style['accent_color'])
            : '#0f766e';
        if ($template === 'ats-classic') {
            $accent = '#111827';
        }
        $font = in_array($style['font_family'] ?? '', ['DejaVu Sans', 'Arial', 'Times New Roman'], true)
            ? $style['font_family']
            : 'DejaVu Sans';
        $fontSize = min(14, max(9, (int)($style['font_size'] ?? 10)));
        $lineHeight = min(2, max(1.1, (float)($style['line_height'] ?? 1.45)));
        $personal = is_array($content['personal'] ?? null) ? $content['personal'] : [];

        $sections = '';
        foreach ($order as $section) {
            if ($section === 'personal' || in_array($section, $hidden, true)) {
                continue;
            }
            $sections .= $this->renderSection($section, $content[$section] ?? null, $language);
        }

        $fullName = $this->e($personal['full_name'] ?? ($language === 'vi' ? 'Họ và tên' : 'Full name'));
        $jobTitle = $this->e($personal['job_title'] ?? '');
        $contacts = $this->renderContacts($personal);
        $title = $this->e(($cv['title'] ?? 'CV') . ' - ' . ($personal['full_name'] ?? ''));
        $printScript = $forPdf ? '' : '<script>window.addEventListener("load",function(){document.documentElement.classList.add("ready")});</script>';

        return <<<HTML
<!doctype html>
<html lang="{$language}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{$title}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f5; color: #1f2937; font-family: '{$font}', sans-serif; font-size: {$fontSize}pt; line-height: {$lineHeight}; }
        .cv-page { width: 210mm; min-height: 297mm; margin: 20px auto; padding: 16mm 17mm; background: #fff; box-shadow: 0 8px 30px rgba(15,23,42,.12); }
        .cv-header { border-bottom: 2px solid {$accent}; padding-bottom: 8mm; margin-bottom: 7mm; }
        h1 { margin: 0; color: {$accent}; font-size: 27pt; line-height: 1.1; letter-spacing: -.4px; }
        .job-title { margin-top: 2mm; font-size: 12pt; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; }
        .contacts { margin-top: 4mm; color: #4b5563; font-size: 9pt; }
        .contacts span, .contacts a { display: inline-block; margin: 0 5mm 2mm 0; }
        .contacts a { color: inherit; text-decoration: none; }
        section { margin: 0 0 6mm; page-break-inside: avoid; }
        h2 { margin: 0 0 3mm; padding-bottom: 1.5mm; color: {$accent}; border-bottom: 1px solid #d1d5db; font-size: 12pt; text-transform: uppercase; letter-spacing: .5px; }
        .entry { margin-bottom: 4mm; page-break-inside: avoid; }
        .entry:last-child { margin-bottom: 0; }
        .entry-head { display: table; width: 100%; }
        .entry-title { display: table-cell; font-weight: 700; font-size: 10.5pt; }
        .entry-date { display: table-cell; width: 32%; text-align: right; color: #6b7280; font-size: 9pt; }
        .entry-subtitle { margin-top: .5mm; color: {$accent}; font-weight: 600; }
        .entry-description, .plain-text { margin-top: 1.5mm; white-space: normal; }
        .entry-description ul { margin: 1mm 0 0 5mm; padding: 0; }
        .chips { margin: -1mm 0 0; }
        .chip { display: inline-block; padding: 1mm 2.5mm; margin: 1mm 2mm 0 0; border: 1px solid #cbd5e1; border-radius: 2mm; }
        .student-modern .cv-header { margin: -16mm -17mm 8mm; padding: 14mm 17mm 10mm; background: {$accent}; color: #fff; border: 0; }
        .student-modern h1, .student-modern .job-title, .student-modern .contacts { color: #fff; }
        .ats-classic h1, .ats-classic h2, .ats-classic .entry-subtitle { color: #111827; }
        .ats-classic .chip { border: 0; padding: 0; margin-right: 3mm; }
        @media print {
            body { background: #fff; }
            .cv-page { margin: 0; box-shadow: none; }
        }
        @media (max-width: 850px) {
            .cv-page { width: 100%; min-height: 0; margin: 0; padding: 8vw; }
            .student-modern .cv-header { margin: -8vw -8vw 8vw; padding: 8vw; }
        }
    </style>
</head>
<body>
    <main class="cv-page {$template}">
        <header class="cv-header">
            <h1>{$fullName}</h1>
            <div class="job-title">{$jobTitle}</div>
            <div class="contacts">{$contacts}</div>
        </header>
        <div class="cv-body">{$sections}</div>
    </main>
    {$printScript}
</body>
</html>
HTML;
    }

    private function renderContacts(array $personal): string
    {
        $parts = [];
        foreach (['email', 'phone', 'address', 'date_of_birth'] as $key) {
            $value = trim((string)($personal[$key] ?? ''));
            if ($value !== '') {
                $parts[] = '<span>' . $this->e($value) . '</span>';
            }
        }
        foreach (['website', 'linkedin'] as $key) {
            $value = trim((string)($personal[$key] ?? ''));
            if ($value !== '') {
                $parts[] = '<a href="' . $this->e($value) . '">' . $this->e($value) . '</a>';
            }
        }
        return implode('', $parts);
    }

    private function renderSection(string $section, mixed $data, string $language): string
    {
        $labels = $language === 'en' ? [
            'summary' => 'Career objective', 'education' => 'Education', 'skills' => 'Skills',
            'projects' => 'Projects', 'experience' => 'Experience', 'activities' => 'Activities',
            'certifications' => 'Certifications', 'awards' => 'Awards', 'languages' => 'Languages',
            'interests' => 'Interests',
        ] : [
            'summary' => 'Mục tiêu nghề nghiệp', 'education' => 'Học vấn', 'skills' => 'Kỹ năng',
            'projects' => 'Dự án', 'experience' => 'Kinh nghiệm', 'activities' => 'Hoạt động',
            'certifications' => 'Chứng chỉ', 'awards' => 'Giải thưởng', 'languages' => 'Ngoại ngữ',
            'interests' => 'Sở thích',
        ];

        if ($section === 'custom_sections') {
            return $this->renderCustomSections(is_array($data) ? $data : []);
        }
        if (in_array($section, ['summary', 'interests'], true)) {
            $text = trim((string)$data);
            return $text === '' ? '' : $this->wrapSection($labels[$section], '<div class="plain-text">' . $this->paragraphs($text) . '</div>');
        }
        if (!is_array($data) || $data === []) {
            return '';
        }
        if ($section === 'skills' || $section === 'languages') {
            $chips = '';
            foreach ($data as $item) {
                if (!is_array($item) || trim((string)($item['name'] ?? '')) === '') {
                    continue;
                }
                $label = trim((string)$item['name']);
                if (trim((string)($item['level'] ?? '')) !== '') {
                    $label .= ' — ' . trim((string)$item['level']);
                }
                $chips .= '<span class="chip">' . $this->e($label) . '</span>';
            }
            return $chips === '' ? '' : $this->wrapSection($labels[$section], '<div class="chips">' . $chips . '</div>');
        }

        $entries = '';
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            [$title, $subtitle, $date, $description, $url] = match ($section) {
                'education' => [$item['school'] ?? '', trim(($item['degree'] ?? '') . ' ' . ($item['major'] ?? '')), $this->dateRange($item), $item['description'] ?? '', ''],
                'experience' => [$item['position'] ?? '', $item['organization'] ?? '', $this->dateRange($item), $item['description'] ?? '', ''],
                'projects' => [$item['name'] ?? '', trim(($item['role'] ?? '') . (($item['technologies'] ?? '') !== '' ? ' · ' . $item['technologies'] : '')), $this->dateRange($item), $item['description'] ?? '', $item['url'] ?? ''],
                'activities' => [$item['role'] ?? '', $item['organization'] ?? '', $this->dateRange($item), $item['description'] ?? '', ''],
                'certifications' => [$item['name'] ?? '', $item['issuer'] ?? '', $item['issued_date'] ?? '', '', $item['url'] ?? ''],
                'awards' => [$item['name'] ?? '', $item['issuer'] ?? '', $item['issued_date'] ?? '', $item['description'] ?? '', ''],
                default => ['', '', '', '', ''],
            };
            if (trim((string)$title) === '' && trim((string)$description) === '') {
                continue;
            }
            $entries .= $this->entry((string)$title, (string)$subtitle, (string)$date, (string)$description, (string)$url);
        }
        return $entries === '' ? '' : $this->wrapSection($labels[$section] ?? $section, $entries);
    }

    private function renderCustomSections(array $sections): string
    {
        $html = '';
        foreach ($sections as $section) {
            if (!is_array($section) || trim((string)($section['title'] ?? '')) === '') {
                continue;
            }
            $entries = '';
            foreach (($section['items'] ?? []) as $item) {
                if (is_array($item)) {
                    $entries .= $this->entry(
                        (string)($item['title'] ?? ''),
                        (string)($item['subtitle'] ?? ''),
                        (string)($item['date'] ?? ''),
                        (string)($item['description'] ?? ''),
                        ''
                    );
                }
            }
            if ($entries !== '') {
                $html .= $this->wrapSection((string)$section['title'], $entries);
            }
        }
        return $html;
    }

    private function entry(string $title, string $subtitle, string $date, string $description, string $url): string
    {
        $titleHtml = $this->e(trim($title));
        if ($url !== '') {
            $titleHtml = '<a href="' . $this->e($url) . '">' . $titleHtml . '</a>';
        }
        $subtitleHtml = trim($subtitle) === '' ? '' : '<div class="entry-subtitle">' . $this->e(trim($subtitle)) . '</div>';
        $descriptionHtml = trim($description) === '' ? '' : '<div class="entry-description">' . $this->paragraphs($description) . '</div>';
        return '<article class="entry"><div class="entry-head"><div class="entry-title">' . $titleHtml
            . '</div><div class="entry-date">' . $this->e($date) . '</div></div>' . $subtitleHtml . $descriptionHtml . '</article>';
    }

    private function wrapSection(string $title, string $content): string
    {
        return '<section><h2>' . $this->e($title) . '</h2>' . $content . '</section>';
    }

    private function dateRange(array $item): string
    {
        $start = trim((string)($item['start_date'] ?? ''));
        $end = !empty($item['current']) ? 'Hiện tại' : trim((string)($item['end_date'] ?? ''));
        return trim($start . (($start !== '' && $end !== '') ? ' – ' : '') . $end);
    }

    private function paragraphs(string $text): string
    {
        $lines = preg_split('/\R/u', trim($text)) ?: [];
        $html = '';
        $list = [];
        $flush = function () use (&$html, &$list): void {
            if ($list !== []) {
                $html .= '<ul><li>' . implode('</li><li>', $list) . '</li></ul>';
                $list = [];
            }
        };
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^[-•]\s*(.+)$/u', $line, $matches)) {
                $list[] = $this->e($matches[1]);
            } else {
                $flush();
                $html .= '<div>' . $this->e($line) . '</div>';
            }
        }
        $flush();
        return $html;
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
