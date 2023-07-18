<?php

namespace JobMarket\Domain\Admin;

class Admin
{
    private $id;
    private $user_id;

    public function __construct(
        string $user_id
    ) {
        $this->user_id = $user_id;
        $this->id = uniqid();
    }

    public static function create(
        string $user_id
    ): static {
        return new static($user_id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
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
}
