<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Application\LoadUser\LoadUserQuery;
use App\Application\LoadUser\LoadUserHandler;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AppUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly LoadUserHandler $loadUserQuery,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->loadUserQuery->query(new LoadUserQuery($identifier));


        if ($user === null) {
            throw new UserNotFoundException(sprintf('No account found for userId "%s".', $identifier));
        }

        return new SecurityUser($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_debug_type($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class;
    }
}
