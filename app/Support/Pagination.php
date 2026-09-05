<?php

namespace JobMarket\Support;

class Pagination
{
    private int $page;
    private int $perPage;
    private int $total;

    public function __construct(int $page = 1, int $perPage = 10, int $total = 0)
    {
        $this->page = max(1, $page);
        $this->perPage = min(100, max(1, $perPage));
        $this->total = max(0, $total);
    }

    public static function fromParams(array $params, int $total = 0, int $defaultPerPage = 10): static
    {
        $page = isset($params["page"]) ? (int)$params["page"] : 1;
        $perPage = isset($params["per_page"]) ? (int)$params["per_page"] : $defaultPerPage;
        return new static($page, $perPage, $total);
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function getLimit(): int
    {
        return $this->perPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function toMeta(?int $total = null): array
    {
        $totalItems = $total !== null ? max(0, $total) : $this->total;
        $totalPages = $totalItems > 0 ? (int)ceil($totalItems / $this->perPage) : 1;

        return [
            "page"        => $this->page,
            "per_page"    => $this->perPage,
            "total"       => $totalItems,
            "total_pages" => $totalPages
        ];
    }
}
