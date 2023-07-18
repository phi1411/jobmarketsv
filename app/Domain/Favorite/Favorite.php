<?php

namespace JobMarket\Domain\Favorite;

class Favorite
{
    private $id;
    private $user_id;
    private $job_id;

    public function __construct(
        string $user_id,
        string $job_id
    ) {
        $this->id = uniqid();
        $this->user_id = $user_id;
        $this->job_id = $job_id;
    }

    public static function create(
        string $user_id,
        string $job_id
    ): static {
        return new static($user_id, $job_id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getJobId(): string
    {
        return $this->job_id;
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

    public function setJobId(string $job_id): self
    {
        $this->job_id = $job_id;
        return $this;
    }
}
