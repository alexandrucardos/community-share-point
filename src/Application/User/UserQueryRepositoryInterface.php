<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Application\User\GetUser\UserView;
use App\Application\User\GetUserByEmailAndGroup\UserCredentialsView;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

interface UserQueryRepositoryInterface
{
    public function findById(
        UuidValueObject $userId,
    ): ?UserView;

    public function findByEmailAndGroupId(
        EmailValueObject $email,
        UuidValueObject $groupId,
    ): ?UserCredentialsView;
}
