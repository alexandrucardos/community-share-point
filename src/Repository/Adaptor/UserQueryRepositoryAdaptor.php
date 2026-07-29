<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Application\LoadUser\LoadUserDto;
use App\Application\Repository\UserQueryRepositoryInterface;
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
    public function findById(UuidValueObject $userId): ?LoadUserDto
    {
         $record = $this->userRepository->find($userId);

        return $record === null ? null : $this->toDomain($record);
    }

    private function toDomain(User $record):LoadUserDto
    {
        return new LoadUserDto(
            id: $record->id,
            hashedPassword: $record->password,
            contactInfo: $record->contactInfo,
            email: $record->email,
            groupId: $record->groupId,
        );
    }
}
