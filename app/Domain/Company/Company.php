<?php

namespace JobMarket\Domain\Company;

class Company
{
    private $id;
    private $user_id;
    private $name;
    private $description;
    private $location;
    private $website;

    public function __construct(
        string $user_id,
        string $name,
        string $description,
        string $location,
        string $website
    )
    {   
        $this->id = uniqid();
        $this->user_id = $user_id;
        $this->name = $name;
        $this->description = $description;
        $this->location = $location;
        $this->website = $website;
    }

    public static function create(
        string $user_id,
        string $name,
        string $description,
        string $location,
        string $website
    ): static
    {
        return new static($user_id, $name, $description, $location, $website);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getWebsite(): string
    {
        return $this->website;
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

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function setwebsite(string $website): self
    {
        $this->website = $website;
        return $this;
    }
}