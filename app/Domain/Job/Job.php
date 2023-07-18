<?php

namespace JobMarket\Domain\Job;

class Job
{
    private $id;
    private $company_id;
    private $title;
    private $description;
    private $requirements;
    private $location;
    private $category;
    private $type;

    public function __construct(
        string $company_id,
        string $title,
        string $description,
        string $requirements,
        string $location,
        string $category,
        string $type
    )
    {
        $this->id = uniqid();
        $this->company_id = $company_id;
        $this->title = $title;
        $this->description = $description;
        $this->requirements = $requirements;
        $this->location = $location;
        $this->category = $category;
        $this->type = $type;
    }

    public static function create(
        string $company_id,
        string $title,
        string $description,
        string $requirements,
        string $location,
        string $category,
        string $type
    ): static
    {
        return new static($company_id, $title, $description, $requirements, $location, $category, $type);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRequirements(): string
    {
        return $this->requirements;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setRequirements(string $requirements): self
    {
        $this->requirements = $requirements;
        return $this;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
}