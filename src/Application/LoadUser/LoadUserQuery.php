<?php

declare(strict_types=1);

namespace App\Application\LoadUser;

use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

final class LoadUserQuery
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailValueObject $emailValueObject,
        private readonly UuidValueObject $uuidValueObject,
    ) {
    }

    public function query(LoadUserDto $command): ?UserEntity
    {
        return $this->userRepository->findByEmailAndGroupId(
            email: ($this->emailValueObject)($command->email),
            groupId: ($this->uuidValueObject)($command->groupId)
        );

    }
}
