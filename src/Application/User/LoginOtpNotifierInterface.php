<?php

declare(strict_types=1);

namespace App\Application\User;

/**
 * Delivery port for login codes.
 *
 * The domain decides what the code is and how long it lives; infrastructure
 * only carries it to the user.
 */
interface LoginOtpNotifierInterface
{
    public function send(
        string $email,
        string $code,
        \DateTimeImmutable $expiresAt,
    ): void;
}
