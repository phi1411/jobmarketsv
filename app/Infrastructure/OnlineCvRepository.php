<?php

namespace JobMarket\Infrastructure;

use JobMarket\Domain\CvBuilder\OnlineCvRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class OnlineCvRepository implements OnlineCvRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
            return;
        }

        $config = Config::env();
        $this->db = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4",
            $config["user"],
            $config["password"],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function listByUser(string $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `online_cvs`
             WHERE `user_id` = ? AND `deleted_at` IS NULL
             ORDER BY `is_primary` DESC, `updated_at` DESC"
        );
        $stmt->execute([$userId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findOwned(string $id, string $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `online_cvs`
             WHERE `id` = ? AND `user_id` = ? AND `deleted_at` IS NULL LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function findPublicBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.* FROM `online_cvs` c
             JOIN `users` u ON u.`id` = c.`user_id`
             WHERE c.`public_slug` = ? AND c.`is_public` = 1
               AND c.`deleted_at` IS NULL AND u.`status` = 'active' LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function countByUser(string $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `online_cvs` WHERE `user_id` = ? AND `deleted_at` IS NULL"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $cv): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `online_cvs`
             (`id`, `user_id`, `title`, `template_key`, `language`, `content_json`, `style_json`,
              `section_order_json`, `hidden_sections_json`, `completion_percent`, `is_primary`,
              `is_public`, `public_slug`, `version`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $cv['id'],
            $cv['user_id'],
            $cv['title'],
            $cv['template_key'],
            $cv['language'],
            $this->encode($cv['content']),
            $this->encode($cv['style']),
            $this->encode($cv['section_order']),
            $this->encode($cv['hidden_sections']),
            $cv['completion_percent'],
            !empty($cv['is_primary']) ? 1 : 0,
            !empty($cv['is_public']) ? 1 : 0,
            $cv['public_slug'],
            $cv['version'] ?? 1,
        ]);
    }

    public function update(string $id, string $userId, array $changes, ?int $expectedVersion = null): bool
    {
        $allowed = [
            'title', 'template_key', 'language', 'content_json', 'style_json',
            'section_order_json', 'hidden_sections_json', 'completion_percent',
            'is_primary', 'is_public', 'public_slug'
        ];
        $sets = [];
        $params = [];

        foreach ($allowed as $column) {
            if (!array_key_exists($column, $changes)) {
                continue;
            }
            $sets[] = "`{$column}` = ?";
            $value = $changes[$column];
            if (str_ends_with($column, '_json')) {
                $value = $this->encode($value);
            } elseif ($column === 'is_primary' || $column === 'is_public') {
                $value = $value ? 1 : 0;
            }
            $params[] = $value;
        }

        if ($sets === []) {
            return true;
        }

        $sets[] = "`version` = `version` + 1";
        $sets[] = "`updated_at` = NOW()";
        $sql = "UPDATE `online_cvs` SET " . implode(', ', $sets)
            . " WHERE `id` = ? AND `user_id` = ? AND `deleted_at` IS NULL";
        $params[] = $id;
        $params[] = $userId;

        if ($expectedVersion !== null) {
            $sql .= " AND `version` = ?";
            $params[] = $expectedVersion;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() === 1;
    }

    public function softDelete(string $id, string $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `online_cvs` SET `deleted_at` = NOW(), `is_public` = 0, `is_primary` = 0,
                    `version` = `version` + 1
             WHERE `id` = ? AND `user_id` = ? AND `deleted_at` IS NULL"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() === 1;
    }

    public function clearPrimary(string $userId, ?string $exceptId = null): void
    {
        $sql = "UPDATE `online_cvs` SET `is_primary` = 0
                WHERE `user_id` = ? AND `deleted_at` IS NULL AND `is_primary` = 1";
        $params = [$userId];
        if ($exceptId !== null) {
            $sql .= " AND `id` <> ?";
            $params[] = $exceptId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function touchExported(string $id): void
    {
        $stmt = $this->db->prepare("UPDATE `online_cvs` SET `last_exported_at` = NOW() WHERE `id` = ?");
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): array
    {
        $row['content'] = $this->decode($row['content_json'] ?? null, []);
        $row['style'] = $this->decode($row['style_json'] ?? null, []);
        $row['section_order'] = $this->decode($row['section_order_json'] ?? null, []);
        $row['hidden_sections'] = $this->decode($row['hidden_sections_json'] ?? null, []);
        unset($row['content_json'], $row['style_json'], $row['section_order_json'], $row['hidden_sections_json']);
        $row['completion_percent'] = (int)($row['completion_percent'] ?? 0);
        $row['is_primary'] = (bool)($row['is_primary'] ?? false);
        $row['is_public'] = (bool)($row['is_public'] ?? false);
        $row['version'] = (int)($row['version'] ?? 1);
        return $row;
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function decode(?string $value, array $fallback): array
    {
        if ($value === null || $value === '') {
            return $fallback;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $fallback;
    }
}
