<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Domain\User\UserEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly UserEntity $user,
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
        return $this->user->getHashedPassword();
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->user->getEmail()->value;
    }
}
