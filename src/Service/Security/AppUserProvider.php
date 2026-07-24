<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Application\LoadUser\LoadUserDto;
use App\Application\LoadUser\LoadUserQuery;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class AppUserProvider implements UserProviderInterface
{
    //todo remove this hardcoded value
    public const DEFAULT_GROUP_ID = '86468911-0B9F-4C7C-8127-3C3B9CBB5DAD';
    public function __construct(
        private readonly LoadUserQuery $loadUserQuery,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $loadUserDto = new LoadUserDto(
            email: $identifier,
            groupId: self::DEFAULT_GROUP_ID
        );

        $user = $this->loadUserQuery->query($loadUserDto);


        if ($user === null) {
            throw new UserNotFoundException(sprintf('No account found for email "%s".', $identifier));
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
