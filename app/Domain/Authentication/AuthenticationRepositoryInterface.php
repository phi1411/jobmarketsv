<?php

namespace JobMarket\Domain\Authentication;

interface AuthenticationRepositoryInterface
{
    public function register(Authentication $user): void;
    public function login(Authentication $user): string;
    public function logout(Authentication $user): void;
    public function getById(string $id): ?Authentication;
    public function getByEmail(string $email): ?Authentication;
    public function getByToken(string $token): ?Authentication;
}