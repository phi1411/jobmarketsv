<?php

namespace JobMarket\Domain\Location;

class Location
{
    private $id;
    private $name;

    public function __construct(
        string $name
    ) {
        $this->id = uniqid();
        $this->name = $name;
    }

    public static function create(
        string $name
    ): static {
        return new static($name);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
