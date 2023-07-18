<?php

namespace JobMarket\Domain\Report;

interface ReportRepositoryInterface
{
    public function getJobs(): array;
    public function getCompanies(): array;
}