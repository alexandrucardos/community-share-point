<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

interface UserRepositoryInterface
{
    public function add(UserEntity $user): void;

    public function update(UserEntity $user): void;

    public function findByEmailAndGroupId(
        EmailValueObject $email,
        UuidValueObject $groupId
    );
}
