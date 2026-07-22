<?php

declare(strict_types=1);

namespace App\Application\CreateUser;

final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
        public string $contactInfo,
    ) {
    }
}
