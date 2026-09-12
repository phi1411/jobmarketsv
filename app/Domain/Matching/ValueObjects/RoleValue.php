<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class RoleValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'title',
        'category_id',
        'category_name',
        'semantic_terms',
    ];

    /**
     * @param list<array<string, mixed>> $semanticTerms
     */
    public function __construct(
        public string $title,
        public ?string $categoryId = null,
        public ?string $categoryName = null,
        public array $semanticTerms = []
    ) {
        ValidationGuard::assertStringLength($this->title, 'title', 255);
        ValidationGuard::assertNoPlaceholder($this->title, 'title');
        ValidationGuard::assertStringLength($this->categoryId, 'category_id', 64, true);
        ValidationGuard::assertStringLength($this->categoryName, 'category_name', 255, true);
        ValidationGuard::assertArrayLimit($this->semanticTerms, 'semantic_terms', 50);
        foreach ($this->semanticTerms as $term) {
            if (isset($term['canonical_name'])) {
                ValidationGuard::assertNoPlaceholder($term['canonical_name'], 'semantic_term.canonical_name');
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'role');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'role');
        }

        $title = ValidationGuard::assertStringLength($data['title'] ?? null, 'title', 255);
        $categoryId = ValidationGuard::assertStringLength($data['category_id'] ?? null, 'category_id', 64, true);
        $categoryName = ValidationGuard::assertStringLength($data['category_name'] ?? null, 'category_name', 255, true);

        $rawTerms = ValidationGuard::assertArrayLimit($data['semantic_terms'] ?? [], 'semantic_terms', 50);
        $terms = [];
        foreach ($rawTerms as $term) {
            if (!is_array($term)) {
                ValidationGuard::assertArrayLimit($term, 'semantic_term_item');
            }
            ValidationGuard::assertNoPii($term, 'semantic_term');
            if ($strict) {
                ValidationGuard::assertStrictKeys($term, ['canonical_name', 'evidence', 'confidence'], 'semantic_term');
            }
            $cName = ValidationGuard::assertStringLength($term['canonical_name'] ?? null, 'canonical_name', 255);
            $evidence = ValidationGuard::assertStringLength($term['evidence'] ?? null, 'evidence', 1000, true);
            $conf = isset($term['confidence']) ? ValidationGuard::assertConfidence($term['confidence'], 'confidence') : 1.0;
            $terms[] = [
                'canonical_name' => $cName,
                'evidence' => $evidence,
                'confidence' => $conf,
            ];
        }

        return new self(
            title: $title,
            categoryId: $categoryId,
            categoryName: $categoryName,
            semanticTerms: $terms
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'semantic_terms' => $this->semanticTerms,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
