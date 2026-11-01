<?php

declare(strict_types=1);

namespace App\Application\User\VerifyLoginOtp;

final class VerifyLoginOtpCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $groupId,
        public readonly string $code,
    ) {
    }
}
