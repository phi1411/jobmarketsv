<?php

namespace JobMarket\Domain\Authentication;

class OAuthIdentity
{
    private string $id;
    private string $userId;
    private string $provider;
    private string $providerSubject;
    private ?string $emailAtLink;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        string $userId,
        string $provider,
        string $providerSubject,
        ?string $emailAtLink = null,
        ?string $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id ?? ("oid-" . uniqid());
        $this->userId = $userId;
        $this->provider = $provider;
        $this->providerSubject = $providerSubject;
        $this->emailAtLink = $emailAtLink;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        string $userId,
        string $provider,
        string $providerSubject,
        ?string $emailAtLink = null
    ): static {
        return new static($userId, $provider, $providerSubject, $emailAtLink);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProviderSubject(): string
    {
        return $this->providerSubject;
    }

    public function getEmailAtLink(): ?string
    {
        return $this->emailAtLink;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->userId,
            "provider" => $this->provider,
            "provider_subject" => $this->providerSubject,
            "email_at_link" => $this->emailAtLink,
            "created_at" => $this->createdAt,
            "updated_at" => $this->updatedAt
        ];
    }
}