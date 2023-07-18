<?php

namespace JobMarket\Domain\Application;

class Application
{
    private $id;
    private $job_id;
    private $developer_id;
    private $cover_letter;
    private $resume;
    private $status;

    public function __construct(
        string $job_id,
        string $developer_id,
        string $cover_letter,
        string $resume,
        string $status
    ) {
        $this->id = uniqid();
        $this->job_id = $job_id;
        $this->developer_id = $developer_id;
        $this->cover_letter = $cover_letter;
        $this->resume = $resume;
        $this->status = $status;
    }

    public static function create(
        string $job_id,
        string $developer_id,
        string $cover_letter,
        string $resume,
        string $status
    ): static {
        return new static($job_id, $developer_id, $cover_letter, $resume, $status);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getJobId(): string
    {
        return $this->job_id;
    }

    public function getDeveloperId(): string
    {
        return $this->developer_id;
    }

    public function getCoverLetter(): string
    {
        return $this->cover_letter;
    }

    public function getResume(): string
    {
        return $this->resume;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setJobId(string $job_id): self
    {
        $this->job_id = $job_id;
        return $this;
    }

    public function setDeveloperId(string $developer_id): self
    {
        $this->developer_id = $developer_id;
        return $this;
    }

    public function setCoverLetter(string $cover_letter): self
    {
        $this->cover_letter = $cover_letter;
        return $this;
    }

    public function setResume(string $resume): self
    {
        $this->resume = $resume;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
