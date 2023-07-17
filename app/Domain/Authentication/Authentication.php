<?php

namespace JobMarket\Domain\Authentication;

use JobMarket\Facades\JWT;

class Authentication
{
    private $id;
    private $name;
    private $email;
    private $password;
    private $role;
    private $token;
    private $token_expires_at;

    public function __construct(
        string $name,
        string $email,
        string $password,
        string $role
    ) {
        $this->id = uniqid();
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->role = $role;
        $this->token = JWT::encode([
            "email"    => $this->email,
            "password" => $this->password
        ]);
        $this->token_expires_at = time() + (1 * 30 * 24 * 3600);
    }

    public static function create(
        string $name,
        string $email,
        string $password,
        string $role
    ): static {
        return new static($name, $email, $password, $role);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpire(): int
    {
        return $this->token_expires_at;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }
    
    public function setExpire(int $expire): self
    {
        $this->token_expires_at = $expire;
        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
}
