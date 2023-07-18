<?php

namespace JobMarket\Domain\Notification;

class Notification
{
    private $id;
    private $user_id;
    private $message;
    private $is_read;

    public function __construct(
        string $user_id,
        string $message,
        bool $is_read
    ) {
        $this->id = uniqid();
        $this->user_id = $user_id;
        $this->message = $message;
        $this->is_read = $is_read;
    }

    public static function create(
        string $user_id,
        string $message,
        bool $is_read
    ): static {
        return new static($user_id, $message, $is_read);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getIsRead(): bool
    {
        return $this->is_read;
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

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setIsRead(bool $is_read): self
    {
        $this->is_read = $is_read;
        return $this;
    }
}
