<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Application\User\GetUser\UserView;
use App\Application\User\GetUserByEmailAndGroup\UserCredentialsView;
use App\Application\User\UserQueryRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Entity\User;
use App\Repository\UserRepository;

final class UserQueryRepositoryAdaptor implements UserQueryRepositoryInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {

    }
    public function findById(UuidValueObject $userId): ?UserView
    {
         $record = $this->userRepository->find($userId->value);

        return $record === null ? null : $this->toDomain($record);
    }

    public function findByEmailAndGroupId(
        EmailValueObject $email,
        UuidValueObject $groupId,
    ): ?UserCredentialsView
    {
        $record = $this->userRepository->findByEmailAndGroupId($email->value, $groupId->value);

        if ($record === null) {
            return null;
        }

        return new UserCredentialsView(
            id: $record->id,
            hashedPassword: $record->password,
            contactInfo: $record->contactInfo,
            email: $record->email,
            groupId: $record->groupId,
        );
    }

    private function toDomain(User $record):UserView
    {
        return new UserView(
            id: $record->id,
            hashedPassword: $record->password,
            contactInfo: $record->contactInfo,
            email: $record->email,
            groupId: $record->groupId,
        );
    }
}
