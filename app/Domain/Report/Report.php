<?php

namespace JobMarket\Domain\Report;

class Report
{
    private $id;
    private $user_id;
    private $report_type;
    private $criteria;

    public function __construct(
        string $user_id,
        string $report_type,
        string $criteria
    ) {
        $this->id = uniqid();
        $this->user_id = $user_id;
        $this->report_type = $report_type;
        $this->criteria = $criteria;
    }

    public static function create(
        string $user_id,
        string $report_type,
        string $criteria
    ): static {
        return new static($user_id, $report_type, $criteria);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getReportType(): string
    {
        return $this->report_type;
    }

    public function getCriteria(): string
    {
        return $this->criteria;
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

    public function setReportType(string $report_type): self
    {
        $this->report_type = $report_type;
        return $this;
    }

    public function setCriteria(string $criteria): self
    {
        $this->criteria = $criteria;
        return $this;
    }
}
