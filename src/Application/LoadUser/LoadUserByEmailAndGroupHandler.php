<?php

declare(strict_types=1);

namespace App\Application\LoadUser;

use App\Application\Repository\UserQueryRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

final class LoadUserByEmailAndGroupHandler
{
    public function __construct(
        private readonly UserQueryRepositoryInterface $userRepository,
        private readonly EmailValueObject $emailValueObject,
        private readonly UuidValueObject $uuidValueObject,
    ) {
    }

    public function query(LoadUserByEmailAndGroupQuery $query): ?LoadUserDto
    {
        return $this->userRepository->findByEmailAndGroupId(
            ($this->emailValueObject)($query->email),
            ($this->uuidValueObject)($query->groupId),
        );
    }
}
