<?php

declare(strict_types=1);

namespace App\Application\LoadUserByEmailAndGroup;

/**
 * Read model for the login lookup (resolve a user from email + group id).
 *
 * Its own type, distinct from {@see \App\Application\LoadUser\LoadUserDto}: this
 * slice's scope is authentication, so it is owned by the login use case rather
 * than shared with the id-based session lookup.
 */
final class LoadUserByEmailAndGroupDto
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
