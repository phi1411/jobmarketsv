<?php

namespace JobMarket\Domain\Review;

class Review
{
    private $id;
    private $company_id;
    private $user_id;
    private $rating;
    private $comment;

    public function __construct(
        string $company_id,
        string $user_id,
        int $rating,
        string $comment
    ) {
        $this->id = uniqid();
        $this->company_id = $company_id;
        $this->user_id = $user_id;
        $this->rating = $rating;
        $this->comment = $comment;
    }

    public static function create(
        string $company_id,
        string $user_id,
        int $rating,
        string $comment
    ): static {
        return new static($company_id, $user_id, $rating, $comment);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setCompanyId(string $company_id): self
    {
        $this->company_id = $company_id;
        return $this;
    }

    public function setUserId(string $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
}
