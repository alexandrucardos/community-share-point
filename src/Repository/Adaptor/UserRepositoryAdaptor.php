<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\User\LoginOtpEntity;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Entity\LoginOtp;
use App\Entity\User;
use App\Repository\LoginOtpRepository;
use App\Repository\UserRepository;

/**
 * Persists the User aggregate: its identity rows and its login codes, which
 * live in their own table but belong to the same aggregate.
 */
final class UserRepositoryAdaptor implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserRepository     $records,
        private readonly LoginOtpRepository $loginOtps,
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

    public function addLoginOtp(LoginOtpEntity $loginOtp): void
    {
        $this->loginOtps->save($this->toLoginOtpRecord($loginOtp));
    }

    public function updateLoginOtp(LoginOtpEntity $loginOtp): void
    {
        $this->loginOtps->save($this->toLoginOtpRecord($loginOtp));
    }

    public function findLatestValidLoginOtp(
        EmailValueObject $email,
        UuidValueObject $groupId,
        \DateTimeImmutable $now,
    ): ?LoginOtpEntity {
        $record = $this->loginOtps->findLatestValid($email->value, $groupId->value, $now);

        return $record === null ? null : $this->toLoginOtpDomain($record);
    }

    public function invalidatePendingLoginOtps(
        EmailValueObject $email,
        UuidValueObject $groupId,
        \DateTimeImmutable $now,
    ): void {
        $this->loginOtps->markPendingUsed($email->value, $groupId->value, $now);
    }

    private function toRecord(UserEntity $user): User
    {
        $record = new User();
        $record->id = $user->getId()->value;
        $record->email = $user->getEmail()->value;
        $record->contactInfo = $user->getContactInfo()->value;
        $record->groupId = $user->getGroupId()->value;

        return $record;
    }

    private function toDomain(User $record): UserEntity
    {
        $user = new UserEntity(
            (new UuidValueObject($this->validator))($record->id),
        );
        $user->setContactInfo((new ContactInfoValueObject($this->validator))($record->contactInfo));
        $user->setEmail((new EmailValueObject($this->validator))($record->email));
        $user->setGroupId((new UuidValueObject($this->validator))($record->groupId));

        return $user;
    }

    private function toLoginOtpRecord(LoginOtpEntity $loginOtp): LoginOtp
    {
        $record = new LoginOtp();
        $record->id = $loginOtp->getId()->value;
        $record->email = $loginOtp->getEmail()->value;
        $record->groupId = $loginOtp->getGroupId()->value;
        $record->hashedCode = $loginOtp->getHashedCode();
        $record->expiresAt = $loginOtp->getExpiresAt();
        $record->createdAt = new \DateTimeImmutable();
        $record->usedAt = $loginOtp->getUsedAt();

        return $record;
    }

    private function toLoginOtpDomain(LoginOtp $record): LoginOtpEntity
    {
        return LoginOtpEntity::restore(
            (new UuidValueObject($this->validator))($record->id),
            (new EmailValueObject($this->validator))($record->email),
            (new UuidValueObject($this->validator))($record->groupId),
            $record->hashedCode,
            $record->expiresAt,
            $record->usedAt,
        );
    }
}
