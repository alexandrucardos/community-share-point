<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class EmailAlreadyRegisteredException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct('An account with this email already exists.');
    }
}
