<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct('No account was found for this email.');
    }
}
