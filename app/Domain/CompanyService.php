<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Company\Company;
use JobMarket\Domain\Company\CompanyRepositoryInterface;

class CompanyService
{
    private $companyRepository;

    public function __construct(CompanyRepositoryInterface $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }
    public function getAll(): array
    {
        return $this->companyRepository->getAll();
    }

    public function create(array $userData): void
    {
        // string $user_id,
        // string $name,
        // string $description,
        // string $location,
        // string $website
        $company = Company::create(
            $userData["user_id"],
            $userData["name"],
            $userData["description"],
            $userData["location"],
            $userData["website"]
        );
        $this->companyRepository->create($company);
    }

    public function getById(string $id): array
    {
        return $this->companyRepository->getById($id);
    }

    public function delete(string $id): void
    {
        $this->companyRepository->delete($id);
    }

    public function update(array $userData): void
    {
        $this->companyRepository->update(Company::create(
            $userData["user_id"],
            $userData["name"],
            $userData["description"],
            $userData["location"],
            $userData["website"]
        ));
    }
}
