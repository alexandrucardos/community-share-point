<?php

declare(strict_types=1);

namespace App\Application\User\GetUserByEmailAndGroup;

use App\Application\User\UserQueryRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

final class GetUserByEmailAndGroupHandler
{
    public function __construct(
        private readonly UserQueryRepositoryInterface $userRepository,
        private readonly EmailValueObject $emailValueObject,
        private readonly UuidValueObject $uuidValueObject,
    ) {
    }

    public function query(GetUserByEmailAndGroupQuery $query): ?UserCredentialsView
    {
        return $this->userRepository->findByEmailAndGroupId(
            ($this->emailValueObject)($query->email),
            ($this->uuidValueObject)($query->groupId),
        );
    }
}
