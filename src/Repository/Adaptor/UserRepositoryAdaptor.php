<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Entity\User;
use App\Repository\UserRepository;

final class UserRepositoryAdaptor implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserRepository     $records,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function add(UserEntity $user): void
    {
        $this->records->save($this->toRecord($user));
    }

    public function update(UserEntity $user): void
    {
        $this->records->save($this->toRecord($user));
    }

    public function findByEmailAndGroupId(
        EmailValueObject $email,
        UuidValueObject $groupId
    ): ?UserEntity
    {
        $record = $this->records->findByEmailAndGroupId($email->value, $groupId->value);

        return $record === null ? null : $this->toDomain($record);
    }

    private function toRecord(UserEntity $user): User
    {
        $record = new User();
        $record->id = $user->getId()->value;
        $record->email = $user->getEmail()->value;
        $record->password = $user->getHashedPassword();
        $record->contactInfo = $user->getContactInfo()->value;
        $record->groupId = $user->getGroupId()->value;

        return $record;
    }

    private function toDomain(User $record): UserEntity
    {
        $user = new UserEntity(
            (new EmailValueObject($this->validator))($record->email),
            (new UuidValueObject($this->validator))($record->groupId),
        );
        $user->setHashedPassword($record->password);
        $user->setContactInfo((new ContactInfoValueObject($this->validator))($record->contactInfo));

        return $user;
    }
}
