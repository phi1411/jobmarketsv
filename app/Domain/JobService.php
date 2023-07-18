<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Job\JobRepositoryInterface;

class JobService
{
    private $jobRepository;

    public function __construct(JobRepositoryInterface $jobRepository)
    {
        $this->jobRepository = $jobRepository;
    }

    public function getAll(): array
    {
        return $this->jobRepository->getAll();
    }
}