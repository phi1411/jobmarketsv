<?php

namespace JobMarket\Domain\Skill;

interface SkillRepositoryInterface
{
    public function getAll(): array;
    public function create(Skill $skill): void;
    public function getById(string $id): array;
    public function update(Skill $skill): void;
    public function delete(string $id): void;
}