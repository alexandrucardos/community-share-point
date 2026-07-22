<?php

declare(strict_types=1);

namespace App\Application\UpdateUser;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $email,
        public string $currentPassword,
        public string $contactInfo,
        public ?string $newPassword = null,
        public ?string $avatarFilename = null,
        public bool $removeAvatar = false,
    ) {
    }
}
