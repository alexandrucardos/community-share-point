<?php

declare(strict_types=1);

namespace App\Application\LoadUser;

final class LoadUserQuery
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}
