<?php

declare(strict_types=1);

namespace App\Application\LoadUser;

final class LoadUserDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $hashedPassword,
        public readonly string $contactInfo,
        public readonly string $email,
        public readonly string $groupId,
    ) {
    }
}
