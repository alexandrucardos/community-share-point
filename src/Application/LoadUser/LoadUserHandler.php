<?php

declare(strict_types=1);

namespace App\Application\LoadUser;

use App\Domain\User\UserEntity;
use App\Application\Repository\UserQueryRepositoryInterface;
use App\Domain\ValueObject\UuidValueObject;

final class LoadUserHandler
{
    public function __construct(
        private readonly UserQueryRepositoryInterface $userRepository,
        private readonly UuidValueObject $uuidValueObject,
    ) {
    }

    public function query(LoadUserQuery $loadUserQuery): ?LoadUserDto
    {
        return $this->userRepository->findById(($this->uuidValueObject)($loadUserQuery->userId));

    }
}
