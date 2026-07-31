<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class InvalidCurrentPasswordException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('The current password you entered is incorrect.');
    }
}
