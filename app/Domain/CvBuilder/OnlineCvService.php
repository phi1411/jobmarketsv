<?php

namespace JobMarket\Domain\CvBuilder;

use JobMarket\Exceptions\AppException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;
use JobMarket\Facades\Config;
use JobMarket\Infrastructure\OnlineCvRepository;

class OnlineCvService
{
    public const MAX_CVS_PER_USER = 20;

    private OnlineCvRepositoryInterface $repository;

    public function __construct(?OnlineCvRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new OnlineCvRepository();
    }

    public function templates(): array
    {
        return CvTemplateCatalog::all();
    }

    public function list(array $user): array
    {
        $userId = $this->studentId($user);
        return array_map([$this, 'toClient'], $this->repository->listByUser($userId));
    }

    public function get(array $user, string $id): array
    {
        $cv = $this->findOwned($this->studentId($user), $id);
        return $this->toClient($cv);
    }

    public function create(array $user, array $input): array
    {
        $userId = $this->studentId($user);
        if ($this->repository->countByUser($userId) >= self::MAX_CVS_PER_USER) {
            throw new ValidationException(['cv' => ['Bạn đã đạt giới hạn 20 CV. Hãy xóa CV không dùng trước khi tạo mới.']]);
        }

        $this->assertOnlyKeys($input, [
            'title', 'template_key', 'language', 'content', 'style',
            'section_order', 'hidden_sections', 'is_primary', 'is_public'
        ]);

        $templateKey = $this->normalizeTemplate($input['template_key'] ?? CvTemplateCatalog::DEFAULT_TEMPLATE);
        $template = CvTemplateCatalog::find($templateKey);
        $language = $this->normalizeLanguage($input['language'] ?? 'vi');
        $content = CvSchema::normalizeContent(
            is_array($input['content'] ?? null) ? $input['content'] : [],
            CvSchema::emptyContent($user)
        );
        $style = CvSchema::normalizeStyle(
            is_array($input['style'] ?? null) ? $input['style'] : [],
            ['accent_color' => $template['default_accent_color']]
        );
        $sectionOrder = array_key_exists('section_order', $input)
            ? CvSchema::normalizeSectionOrder($input['section_order'])
            : CvSchema::DEFAULT_SECTION_ORDER;
        $hiddenSections = array_key_exists('hidden_sections', $input)
            ? CvSchema::normalizeHiddenSections($input['hidden_sections'])
            : [];
        $isPrimary = $this->repository->countByUser($userId) === 0
            || $this->normalizeBool($input['is_primary'] ?? false, 'is_primary');
        $isPublic = $this->normalizeBool($input['is_public'] ?? false, 'is_public');

        if ($isPublic) {
            $this->assertPublishable($content);
        }
        if ($isPrimary) {
            $this->repository->clearPrimary($userId);
        }

        $cv = [
            'id' => 'cv-' . bin2hex(random_bytes(12)),
            'user_id' => $userId,
            'title' => $this->normalizeTitle($input['title'] ?? 'CV sinh viên'),
            'template_key' => $templateKey,
            'language' => $language,
            'content' => $content,
            'style' => $style,
            'section_order' => $sectionOrder,
            'hidden_sections' => $hiddenSections,
            'completion_percent' => CvSchema::completionPercent($content),
            'is_primary' => $isPrimary,
            'is_public' => $isPublic,
            'public_slug' => $this->generateSlug(),
            'version' => 1,
        ];
        $this->repository->create($cv);

        return $this->get($user, $cv['id']);
    }

    public function update(array $user, string $id, array $input): array
    {
        $userId = $this->studentId($user);
        $current = $this->findOwned($userId, $id);
        $this->assertOnlyKeys($input, [
            'title', 'template_key', 'language', 'content', 'style', 'section_order',
            'hidden_sections', 'is_primary', 'is_public', 'expected_version'
        ]);

        $expectedVersion = null;
        if (array_key_exists('expected_version', $input)) {
            $expectedVersion = filter_var($input['expected_version'], FILTER_VALIDATE_INT);
            if ($expectedVersion === false || $expectedVersion < 1) {
                throw new ValidationException(['expected_version' => ['Phiên bản CV phải là số nguyên dương.']]);
            }
            if ($expectedVersion !== (int)$current['version']) {
                throw new AppException(
                    'CV đã được cập nhật ở một phiên khác. Hãy tải dữ liệu mới trước khi lưu lại.',
                    409,
                    ['expected_version' => $expectedVersion, 'current_version' => (int)$current['version']]
                );
            }
        }

        $changes = [];
        $nextContent = $current['content'];
        if (array_key_exists('title', $input)) {
            $changes['title'] = $this->normalizeTitle($input['title']);
        }
        if (array_key_exists('template_key', $input)) {
            $changes['template_key'] = $this->normalizeTemplate($input['template_key']);
        }
        if (array_key_exists('language', $input)) {
            $changes['language'] = $this->normalizeLanguage($input['language']);
        }
        if (array_key_exists('content', $input)) {
            $nextContent = CvSchema::normalizeContent($input['content'], $current['content']);
            $changes['content_json'] = $nextContent;
            $changes['completion_percent'] = CvSchema::completionPercent($nextContent);
        }
        if (array_key_exists('style', $input)) {
            $changes['style_json'] = CvSchema::normalizeStyle($input['style'], $current['style']);
        }
        if (array_key_exists('section_order', $input)) {
            $changes['section_order_json'] = CvSchema::normalizeSectionOrder($input['section_order']);
        }
        if (array_key_exists('hidden_sections', $input)) {
            $changes['hidden_sections_json'] = CvSchema::normalizeHiddenSections($input['hidden_sections']);
        }
        if (array_key_exists('is_primary', $input)) {
            $primary = $this->normalizeBool($input['is_primary'], 'is_primary');
            if (!$primary && !empty($current['is_primary'])) {
                throw new ValidationException(['is_primary' => ['Không thể bỏ CV chính trực tiếp. Hãy đặt một CV khác làm CV chính.']]);
            }
            if ($primary) {
                $this->repository->clearPrimary($userId, $id);
                $changes['is_primary'] = true;
            }
        }
        if (array_key_exists('is_public', $input)) {
            $public = $this->normalizeBool($input['is_public'], 'is_public');
            if ($public) {
                $this->assertPublishable($nextContent);
            }
            $changes['is_public'] = $public;
        }

        if (!$this->repository->update($id, $userId, $changes, $expectedVersion)) {
            throw new AppException(
                'Không thể lưu vì CV vừa được cập nhật ở nơi khác.',
                409,
                ['current_version' => (int)($this->findOwned($userId, $id)['version'] ?? 0)]
            );
        }
        return $this->get($user, $id);
    }

    public function duplicate(array $user, string $id): array
    {
        $userId = $this->studentId($user);
        if ($this->repository->countByUser($userId) >= self::MAX_CVS_PER_USER) {
            throw new ValidationException(['cv' => ['Bạn đã đạt giới hạn 20 CV.']]);
        }
        $source = $this->findOwned($userId, $id);
        $copy = $source;
        $copy['id'] = 'cv-' . bin2hex(random_bytes(12));
        $copy['title'] = $this->normalizeTitle($source['title'] . ' - Bản sao');
        $copy['is_primary'] = false;
        $copy['is_public'] = false;
        $copy['public_slug'] = $this->generateSlug();
        $copy['version'] = 1;
        $this->repository->create($copy);
        return $this->get($user, $copy['id']);
    }

    public function delete(array $user, string $id): void
    {
        $userId = $this->studentId($user);
        $current = $this->findOwned($userId, $id);
        if (!$this->repository->softDelete($id, $userId)) {
            throw new NotFoundException('CV không tồn tại hoặc đã bị xóa.');
        }

        if (!empty($current['is_primary'])) {
            $remaining = $this->repository->listByUser($userId);
            if ($remaining !== []) {
                $next = $remaining[0];
                $this->repository->clearPrimary($userId, $next['id']);
                $this->repository->update($next['id'], $userId, ['is_primary' => true], (int)$next['version']);
            }
        }
    }

    public function publicBySlug(string $slug): array
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{12,64}$/', $slug)) {
            throw new NotFoundException('Liên kết CV không tồn tại.');
        }
        $cv = $this->repository->findPublicBySlug($slug);
        if ($cv === null) {
            throw new NotFoundException('CV không tồn tại hoặc chủ CV đã tắt chia sẻ.');
        }
        return $this->toClient($cv, true);
    }

    public function ownedRaw(array $user, string $id): array
    {
        return $this->findOwned($this->studentId($user), $id);
    }

    public function publicRaw(string $slug): array
    {
        $this->publicBySlug($slug);
        return $this->repository->findPublicBySlug($slug);
    }

    public function markExported(string $id): void
    {
        $this->repository->touchExported($id);
    }

    private function findOwned(string $userId, string $id): array
    {
        $cv = $this->repository->findOwned($id, $userId);
        if ($cv === null) {
            throw new NotFoundException('CV không tồn tại hoặc bạn không có quyền truy cập.');
        }
        return $cv;
    }

    private function studentId(array $user): string
    {
        $role = strtolower((string)($user['role'] ?? ''));
        if (!in_array($role, ['student', 'developer'], true)) {
            throw new AuthorizationException('Chỉ tài khoản sinh viên mới được sử dụng trình tạo CV.');
        }
        $id = trim((string)($user['id'] ?? ''));
        if ($id === '') {
            throw new AuthorizationException('Không xác định được tài khoản sinh viên.');
        }
        return $id;
    }

    private function normalizeTitle(mixed $value): string
    {
        if (!is_scalar($value)) {
            throw new ValidationException(['title' => ['Tên CV phải là chuỗi.']]);
        }
        $title = trim((string)$value);
        $length = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        if ($length < 2 || $length > 120) {
            throw new ValidationException(['title' => ['Tên CV phải từ 2 đến 120 ký tự.']]);
        }
        return $title;
    }

    private function normalizeTemplate(mixed $value): string
    {
        $key = trim((string)$value);
        if (!in_array($key, CvTemplateCatalog::keys(), true)) {
            throw new ValidationException(['template_key' => ['Mẫu CV không tồn tại.']]);
        }
        return $key;
    }

    private function normalizeLanguage(mixed $value): string
    {
        $language = strtolower(trim((string)$value));
        if (!in_array($language, ['vi', 'en'], true)) {
            throw new ValidationException(['language' => ['Ngôn ngữ hỗ trợ: vi, en.']]);
        }
        return $language;
    }

    private function normalizeBool(mixed $value, string $field): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (in_array($value, [0, 1, '0', '1'], true)) {
            return (bool)$value;
        }
        throw new ValidationException([$field => ['Giá trị phải là true hoặc false.']]);
    }

    private function assertPublishable(array $content): void
    {
        $personal = $content['personal'] ?? [];
        $errors = [];
        if (trim((string)($personal['full_name'] ?? '')) === '') {
            $errors['content.personal.full_name'][] = 'Cần có họ tên trước khi công khai CV.';
        }
        if (trim((string)($personal['email'] ?? '')) === '' && trim((string)($personal['phone'] ?? '')) === '') {
            $errors['content.personal'][] = 'Cần có email hoặc số điện thoại trước khi công khai CV.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors, 'CV chưa đủ thông tin để công khai.');
        }
    }

    private function assertOnlyKeys(array $input, array $allowed): void
    {
        $unknown = array_diff(array_keys($input), $allowed);
        if ($unknown !== []) {
            throw new ValidationException(['request' => ['Trường không được hỗ trợ: ' . implode(', ', $unknown) . '.']]);
        }
    }

    private function generateSlug(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    private function toClient(array $cv, bool $publicView = false): array
    {
        $baseUrl = Config::appUrl();
        $slug = (string)$cv['public_slug'];
        $result = $cv;
        unset($result['user_id'], $result['deleted_at']);
        $result['template'] = CvTemplateCatalog::find((string)$cv['template_key']);
        $result['public_url'] = !empty($cv['is_public']) ? "{$baseUrl}/cv/{$slug}" : null;
        $result['public_pdf_url'] = !empty($cv['is_public']) ? "{$baseUrl}/cv/{$slug}/export.pdf" : null;
        $result['export_pdf_url'] = $publicView ? $result['public_pdf_url'] : "{$baseUrl}/student/cvs/{$cv['id']}/export.pdf";
        if (!$publicView) {
            $result['public_slug'] = $slug;
        } else {
            unset($result['public_slug'], $result['is_primary'], $result['last_exported_at']);
        }
        return $result;
    }
}
