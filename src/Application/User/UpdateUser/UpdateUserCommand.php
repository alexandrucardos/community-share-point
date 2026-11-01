<?php

declare(strict_types=1);

namespace App\Application\User\UpdateUser;

final class UpdateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $contactInfo,
        public readonly string $groupId,
    ) {
    }
}
