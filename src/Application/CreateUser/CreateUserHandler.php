<?php

declare(strict_types=1);

namespace App\Application\CreateUser;

use App\Domain\User\Exception\EmailAlreadyRegisteredException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\PasswordValueObject;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly EmailValueObject $emailValidator,
        private readonly PasswordValueObject $passwordValidator,
        private readonly UuidInterface $uuid,
    ) {
    }

    public function handle(CreateUserCommand $command): void
    {
        $passwordVO = ($this->passwordValidator)($command->plainPassword);

        if ($this->userRepository->findByEmail($command->email) !== null) {
            throw new EmailAlreadyRegisteredException($command->email);
        }

        $user = new UserEntity($this->uuid->generate());
        $user->setEmail(($this->emailValidator)($command->email));
        //todo add a VO for contact info
        $user->setContactInfo($command->contactInfo);
        //todo add a vo for hasher
        $user->setPassword($this->passwordHasher->hash($passwordVO));
        //todo add a group_id
        $user->setGroupId('aaa');

        $this->userRepository->add($user);
    }
}
