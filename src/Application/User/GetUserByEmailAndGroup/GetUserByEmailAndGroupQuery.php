<?php

declare(strict_types=1);

namespace App\Application\User\GetUserByEmailAndGroup;

final class GetUserByEmailAndGroupQuery
{
    public function __construct(
        public readonly string $email,
        public readonly string $groupId,
    ) {
    }
}
