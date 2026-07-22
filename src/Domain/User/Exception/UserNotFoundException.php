<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('No account found for email "%s".', $email));
    }
}
