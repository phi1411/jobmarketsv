<?php

namespace JobMarket\Domain;

use JobMarket\Domain\Authentication\Authentication;
use JobMarket\Domain\Authentication\AuthenticationRepositoryInterface;

class AuthenticationService
{
    private $authenticationRepository;

    public function __construct(AuthenticationRepositoryInterface $authenticationRepository)
    {
        $this->authenticationRepository = $authenticationRepository;
    }

    public function register(array $userData): void
    {
        $user = Authentication::create(
            $userData["name"],
            $userData["email"],
            $userData["password"],
            $userData["role"]
        );
        $this->authenticationRepository->register($user);
    }

    public function logout(string $email): void
    {
        $this->authenticationRepository->logout($email);
    }

    public function login(array $userData): string
    {
        $user = Authentication::create(
            $userData["name"],
            $userData["email"],
            $userData["password"],
            $userData["role"]
        );

        return $this->authenticationRepository->login($user);
    }
}