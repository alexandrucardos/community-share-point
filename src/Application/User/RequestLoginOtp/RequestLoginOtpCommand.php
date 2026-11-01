<?php

declare(strict_types=1);

namespace App\Application\User\RequestLoginOtp;

final class RequestLoginOtpCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $groupId,
    ) {
    }
}
