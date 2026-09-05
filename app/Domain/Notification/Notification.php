<?php

namespace JobMarket\Domain\Notification;

class Notification
{
    private string $id;
    private string $user_id;
    private string $type;
    private string $title;
    private string $message;
    private ?array $data = null;
    private ?string $read_at = null;
    private ?string $created_at = null;

    public function __construct(
        string $user_id,
        string $title,
        string $message,
        string $type = "general",
        ?array $data = null,
        ?string $id = null
    ) {
        $this->id = $id ?? ("notif-" . uniqid());
        $this->user_id = $user_id;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
        $this->created_at = date("Y-m-d H:i:s");
    }

    public static function create(
        string $user_id,
        string $title,
        string $message,
        string $type = "general",
        ?array $data = null
    ): static {
        return new static($user_id, $title, $message, $type, $data);
    }

    public static function fromArray(array $row): static
    {
        $id = $row["id"] ?? ("notif-" . uniqid());
        $userId = $row["user_id"] ?? "";
        $title = $row["title"] ?? "";
        $message = $row["message"] ?? "";
        $type = $row["type"] ?? "general";

        $data = $row["data"] ?? null;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : null;
        }

        $notif = new static($userId, $title, $message, $type, $data, $id);
        $notif->read_at = $row["read_at"] ?? null;
        $notif->created_at = $row["created_at"] ?? null;

        return $notif;
    }

    public function toArray(): array
    {
        return [
            "id"         => $this->id,
            "user_id"    => $this->user_id,
            "type"       => $this->type,
            "title"      => $this->title,
            "message"    => $this->message,
            "data"       => $this->data,
            "is_read"    => $this->read_at !== null,
            "read_at"    => $this->read_at,
            "created_at" => $this->created_at,
        ];
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->read_at = date("Y-m-d H:i:s");
        }
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->user_id; }
    public function getType(): string { return $this->type; }
    public function getTitle(): string { return $this->title; }
    public function getMessage(): string { return $this->message; }
    public function getData(): ?array { return $this->data; }
    public function getReadAt(): ?string { return $this->read_at; }
    public function getCreatedAt(): ?string { return $this->created_at; }
}
