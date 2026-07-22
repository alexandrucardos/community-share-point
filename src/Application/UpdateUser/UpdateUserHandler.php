<?php

declare(strict_types=1);

namespace App\Application\UpdateUser;

use App\Domain\User\Exception\InvalidCurrentPasswordException;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\PasswordValueObject;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PasswordValueObject $passwordValidator,
    ) {
    }

    public function handle(UpdateUserCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email);

        if ($user === null) {
            throw new UserNotFoundException($command->email);
        }

        if (!$this->passwordHasher->verify($user->getPassword(), $command->currentPassword)) {
            throw new InvalidCurrentPasswordException();
        }

        if ($command->newPassword !== null) {
            $passwordVO = ($this->passwordValidator)($command->newPassword);

            $user->setPassword($this->passwordHasher->hash($passwordVO));
        }

        $user->setContactInfo($command->contactInfo);

        if ($command->avatarFilename !== null) {
            $user->setAvatarFilename($command->avatarFilename);
        } elseif ($command->removeAvatar) {
            $user->setAvatarFilename('');
        }

        $this->userRepository->update($user);
    }
}
