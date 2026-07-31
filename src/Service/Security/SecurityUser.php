<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Application\User\GetUser\UserView;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly UserView $loadUserDto,
    ) {
    }

    public function getUserView(): UserView
    {
        return $this->loadUserDto;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->loadUserDto->hashedPassword;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->loadUserDto->id;
    }
}
