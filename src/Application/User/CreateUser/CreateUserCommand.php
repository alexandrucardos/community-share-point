<?php

declare(strict_types=1);

namespace App\Application\User\CreateUser;

final class CreateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $plainPassword,
        public readonly string $contactInfo,
        public readonly string $groupId,
    ) {
    }
}
