<?php

declare(strict_types=1);

namespace App\Application\User\UpdateUser;

use App\Domain\Event\DomainEventPublisherInterface;
use App\Domain\User\Event\UserUpdatedEvent;
use App\Domain\User\Exception\InvalidCurrentPasswordException;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\PasswordValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;

final class UpdateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly DomainEventPublisherInterface $domainEventPublisher,
    ) {
    }

    public function handle(UpdateUserCommand $command): void
    {
        $emailVO = (new EmailValueObject($this->validator))($command->email);
        $groupIdVO = (new UuidValueObject($this->validator))($command->groupId);

        $user = $this->userRepository->findByEmailAndGroupId(
            $emailVO,
            $groupIdVO
        );

        if ($user === null) {
            throw new UserNotFoundException($command->email);
        }

        if (!$this->passwordHasher->verify($user->getHashedPassword(), $command->currentPassword)) {
            throw new InvalidCurrentPasswordException();
        }

        if ($command->newPassword !== null) {
            $passwordVO = (new PasswordValueObject($this->validator))($command->newPassword);

            $user->setHashedPassword($this->passwordHasher->hash($passwordVO));
        }

        $contactInfoVO = (new ContactInfoValueObject($this->validator))($command->contactInfo);
        $user->setContactInfo($contactInfoVO);

        $this->userRepository->update($user);

        $this->domainEventPublisher->publish(new UserUpdatedEvent(
            userId: $user->getId()->value,
            email: $user->getEmail()->value,
            contactInfo: $user->getContactInfo()->value,
            groupId: $user->getGroupId()->value,
            passwordChanged: $command->newPassword !== null,
        ));
    }
}
