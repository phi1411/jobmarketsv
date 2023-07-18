<?php

namespace JobMarket\Domain\Favorite;

interface FavoriteRepositoryInterface
{
    public function getAll(string $user_id): array;
    public function create(Favorite $favorite): void;
    public function delete(string $id): void;
}