<?php

namespace JobMarket\Infrastructure;
use JobMarket\Domain\Report\ReportRepositoryInterface;
use JobMarket\Facades\Config;
use PDO;

class ReportRepository implements ReportRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $config = Config::env();
        $this->db = new PDO(
            "mysql:dbname={$config['dbname']};host={$config['host']}",
            $config["user"],
            $config["password"]
        );
    }
    public function getJobs(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM reports WHERE report_type = jobs"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getCompanies(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM reports WHERE report_type = companies"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}