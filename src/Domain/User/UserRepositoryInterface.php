<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function add(UserEntity $user): void;

    public function update(UserEntity $user): void;

    public function findByEmail(string $email): ?UserEntity;

    public function findById(string $id): ?UserEntity;

    /**
     * @return UserEntity[]
     */
    public function findAllByGroupId(string $groupId): array;
}
