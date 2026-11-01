<?php

declare(strict_types=1);

namespace App\Application\User\VerifyLoginOtp;

use App\Domain\User\Exception\InvalidLoginOtpException;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;

/**
 * Consumes a login code: on success it is marked used, on failure it stays
 * available so a single typo does not force a new email.
 */
final class VerifyLoginOtpHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ValidatorInterface      $validator,
    )
    {
    }

    public function handle(VerifyLoginOtpCommand $command): void
    {
        $now = new \DateTimeImmutable();

        try {
            $email = (new EmailValueObject($this->validator))($command->email);
            $groupId = (new UuidValueObject($this->validator))($command->groupId);
        } catch (\InvalidArgumentException) {
            throw new InvalidLoginOtpException('That login code is not valid or has expired.');
        }

        $otp = $this->userRepository->findLatestValidLoginOtp($email, $groupId, $now);

        if ($otp === null || !$otp->matches($command->code, $now)) {
            throw new InvalidLoginOtpException('That login code is not valid or has expired.');
        }

        $otp->markUsed($now);

        $this->userRepository->updateLoginOtp($otp);
    }
}
