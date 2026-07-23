<?php

declare(strict_types=1);

namespace App\Application\UpdateUser;

final class UpdateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $currentPassword,
        public readonly string $contactInfo,
        public readonly ?string $newPassword = null,
        public readonly ?string $avatarFilename = null,
        public readonly bool $removeAvatar = false,
    ) {
    }
}
