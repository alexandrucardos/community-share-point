<?php

declare(strict_types=1);

namespace App\Application\CreateUser;

use App\Domain\User\Exception\EmailAlreadyRegisteredException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\PasswordValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Service\Security\AppUserProvider;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly EmailValueObject $emailValueObject,
        private readonly PasswordValueObject $passwordValueObject,
        private readonly ContactInfoValueObject $contactInfoValueObject,
        private readonly UuidValueObject $uuidValueObject,
        private readonly UuidInterface $uuid,
    ) {
    }

    public function handle(CreateUserCommand $command): void
    {
        $emailVO = ($this->emailValueObject)($command->email);
        $passwordVO = ($this->passwordValueObject)($command->plainPassword);
        $groupIdVO = ($this->uuidValueObject)(AppUserProvider::DEFAULT_GROUP_ID);

        $existingUser = $this->userRepository->findByEmailAndGroupId(
            $emailVO,
            $groupIdVO
        );

        if ($existingUser !== null) {
            throw new EmailAlreadyRegisteredException($command->email);
        }

        $user = new UserEntity(($this->uuidValueObject)($this->uuid->generate()));
        $user->setEmail($emailVO);
        $user->setContactInfo(($this->contactInfoValueObject)($command->contactInfo));
        $user->setHashedPassword($this->passwordHasher->hash($passwordVO));
        $user->setGroupId($groupIdVO);

        $this->userRepository->add($user);
    }
}
