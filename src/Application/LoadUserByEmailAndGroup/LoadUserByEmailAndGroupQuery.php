<?php

declare(strict_types=1);

namespace App\Application\LoadUserByEmailAndGroup;

final class LoadUserByEmailAndGroupQuery
{
    public function __construct(
        public readonly string $email,
        public readonly string $groupId,
    ) {
    }
}
