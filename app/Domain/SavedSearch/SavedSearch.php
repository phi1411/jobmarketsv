<?php

namespace JobMarket\Domain\SavedSearch;

class SavedSearch
{
    private $id;
    private $user_id;
    private $criteria;

    public function __construct(
        string $user_id,
        string $criteria
    )
    {
        $this->id = uniqid();
        $this->user_id = $user_id;
        $this->criteria = $criteria;
    }

    public static function create(
        string $user_id,
        string $criteria
    ): static
    {
        return new static($user_id, $criteria);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getCriteria(): string
    {
        return $this->criteria;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setUserId(string $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function setCriteria(string $criteria): self
    {
        $this->criteria = $criteria;
        return $this;
    }
}