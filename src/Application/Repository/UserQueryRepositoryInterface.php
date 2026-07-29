<?php

declare(strict_types=1);

namespace App\Application\Repository;

use App\Application\LoadUser\LoadUserDto;
use App\Domain\ValueObject\UuidValueObject;

interface UserQueryRepositoryInterface
{
    public function findById(
        UuidValueObject $userId,
    ): ?LoadUserDto;
}
