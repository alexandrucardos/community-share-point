<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

/**
 * Persistence port for the User aggregate.
 *
 * One repository serves the whole aggregate, including its login codes: an
 * issued code is a credential of a user account, not an aggregate of its own.
 * Storage may well keep the codes in their own table; that is an
 * infrastructure decision this contract does not expose.
 */
interface UserRepositoryInterface
{
    public function add(UserEntity $user): void;

    public function update(UserEntity $user): void;

    public function findByEmailAndGroupId(
        EmailValueObject $email,
        UuidValueObject $groupId
    ):?UserEntity;

    public function addLoginOtp(LoginOtpEntity $loginOtp): void;

    public function updateLoginOtp(LoginOtpEntity $loginOtp): void;

    /**
     * The most recent unused, unexpired code for this email within the group.
     */
    public function findLatestValidLoginOtp(
        EmailValueObject $email,
        UuidValueObject $groupId,
        \DateTimeImmutable $now,
    ): ?LoginOtpEntity;

    /**
     * Discard every code still pending for this email/group, so a newly
     * issued code is the only one that can be used.
     */
    public function invalidatePendingLoginOtps(
        EmailValueObject $email,
        UuidValueObject $groupId,
        \DateTimeImmutable $now,
    ): void;
}
