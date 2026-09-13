<?php

declare(strict_types=1);

namespace App\Application\User\CreateUser;

use App\Domain\Event\DomainEventPublisherInterface;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Exception\EmailAndGroupAlreadyRegisteredException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\PasswordValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly UuidInterface $uuid,
        private readonly DomainEventPublisherInterface $domainEventPublisher,
    ) {
    }

    public function handle(CreateUserCommand $command): void
    {
        $emailVO = (new EmailValueObject($this->validator))($command->email);
        $passwordVO = (new PasswordValueObject($this->validator))($command->plainPassword);
        $groupIdVO = (new UuidValueObject($this->validator))($command->groupId);

        $existingUser = $this->userRepository->findByEmailAndGroupId(
            $emailVO,
            $groupIdVO
        );

        if ($existingUser !== null) {
            throw new EmailAndGroupAlreadyRegisteredException($command->email);
        }

        $user = new UserEntity((new UuidValueObject($this->validator))($this->uuid->generate()));
        $user->setEmail($emailVO);
        $user->setContactInfo((new ContactInfoValueObject($this->validator))($command->contactInfo));
        $user->setHashedPassword($this->passwordHasher->hash($passwordVO));
        $user->setGroupId($groupIdVO);

        $this->userRepository->add($user);

        $this->domainEventPublisher->publish(new UserCreatedEvent(
            userId: $user->getId()->value,
            email: $user->getEmail()->value,
            contactInfo: $user->getContactInfo()->value,
            groupId: $user->getGroupId()->value,
        ));
    }
}
