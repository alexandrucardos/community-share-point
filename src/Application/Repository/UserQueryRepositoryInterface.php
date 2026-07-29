<?php

declare(strict_types=1);

namespace App\Application\Repository;

use App\Application\LoadUser\LoadUserDto;
use App\Application\LoadUserByEmailAndGroup\LoadUserByEmailAndGroupDto;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

interface UserQueryRepositoryInterface
{
    public function findById(
        UuidValueObject $userId,
    ): ?LoadUserDto;

    public function findByEmailAndGroupId(
        EmailValueObject $email,
        UuidValueObject $groupId,
    ): ?LoadUserByEmailAndGroupDto;
}
