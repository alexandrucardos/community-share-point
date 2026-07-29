<?php

declare(strict_types=1);

namespace App\Application\LoadUserByEmailAndGroup;

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

    public function query(LoadUserByEmailAndGroupQuery $query): ?LoadUserByEmailAndGroupDto
    {
        return $this->userRepository->findByEmailAndGroupId(
            ($this->emailValueObject)($query->email),
            ($this->uuidValueObject)($query->groupId),
        );
    }
}
