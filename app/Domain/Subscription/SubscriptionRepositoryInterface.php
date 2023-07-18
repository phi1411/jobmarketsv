<?php

namespace JobMarket\Domain\Subscription;

interface SubscriptionRepositoryInterface
{
    public function create(Subscription $sub): void;
    public function delete(string $id): void;
}