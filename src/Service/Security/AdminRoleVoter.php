<?php

declare(strict_types=1);

namespace App\Service\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants the configured administrator email the ROLE_ADMIN role.
 *
 * The role is evaluated dynamically so the user entity and database schema do
 * not need an application-wide role column.
 *
 * @extends Voter<string, mixed>
 */
final class AdminRoleVoter extends Voter
{
    public function __construct(
        #[Autowire('%env(default::ADMIN_EMAIL)%')]
        private readonly ?string $adminEmail,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'ROLE_ADMIN';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof SecurityUser || $this->adminEmail === null || trim($this->adminEmail) === '') {
            return false;
        }

        return strcasecmp(trim($user->getUserView()->email), trim($this->adminEmail)) === 0;
    }
}
