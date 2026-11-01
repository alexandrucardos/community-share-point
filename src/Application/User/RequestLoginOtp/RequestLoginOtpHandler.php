<?php

declare(strict_types=1);

namespace App\Application\User\RequestLoginOtp;

use App\Application\User\GetUserByEmailAndGroup\GetUserByEmailAndGroupHandler;
use App\Application\User\GetUserByEmailAndGroup\GetUserByEmailAndGroupQuery;
use App\Application\User\LoginOtpNotifierInterface;
use App\Domain\User\LoginOtpEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;

/**
 * Issues a short-lived login code and mails it to the user.
 *
 * Nothing is reported back to the caller for an unknown email/group: the
 * handler stops silently so the login page cannot be used to discover which
 * addresses belong to a group.
 */
final class RequestLoginOtpHandler
{
    private const CODE_MIN = 100000;
    private const CODE_MAX = 999999;

    public function __construct(
        private readonly GetUserByEmailAndGroupHandler $loadUserByEmailAndGroup,
        private readonly UserRepositoryInterface       $userRepository,
        private readonly LoginOtpNotifierInterface     $notifier,
        private readonly UuidInterface                 $uuid,
        private readonly ValidatorInterface            $validator,
    )
    {
    }

    public function handle(RequestLoginOtpCommand $command): void
    {
        try {
            $user = $this->loadUserByEmailAndGroup->query(
                new GetUserByEmailAndGroupQuery($command->email, $command->groupId),
            );
        } catch (\InvalidArgumentException) {
            return;
        }

        if ($user === null) {
            return;
        }

        $now = new \DateTimeImmutable();
        $email = (new EmailValueObject($this->validator))($user->email);
        $groupId = (new UuidValueObject($this->validator))($user->groupId);
        $code = (string)random_int(self::CODE_MIN, self::CODE_MAX);

        $otp = LoginOtpEntity::issue(
            (new UuidValueObject($this->validator))($this->uuid->generate()),
            $email,
            $groupId,
            $code,
            $now,
        );

        // A new code supersedes any still-pending one.
        $this->userRepository->invalidatePendingLoginOtps($email, $groupId, $now);
        $this->userRepository->addLoginOtp($otp);

        $this->notifier->send($user->email, $code, $otp->getExpiresAt());
    }
}
