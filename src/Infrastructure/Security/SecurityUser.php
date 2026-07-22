<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\UserEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private UserEntity $user,
    ) {
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->user->getPassword();
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->user->getEmail()->value;
    }
}
