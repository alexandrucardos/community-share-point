<?php

declare(strict_types=1);

namespace App\Tests\Service\Security;

use App\Application\User\GetUser\UserView;
use App\Service\Security\AdminRoleVoter;
use App\Service\Security\SecurityUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class AdminRoleVoterTest extends TestCase
{
    public function testConfiguredEmailReceivesAdminRoleCaseInsensitively(): void
    {
        $voter = new AdminRoleVoter(' ALEC.CARDOS@GMAIL.COM ');
        $token = $this->tokenFor('alec.cardos@gmail.com');

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, ['ROLE_ADMIN']));
    }

    public function testOtherEmailsDoNotReceiveAdminRole(): void
    {
        $voter = new AdminRoleVoter('alec.cardos@gmail.com');
        $token = $this->tokenFor('other@example.com');

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, ['ROLE_ADMIN']));
    }

    private function tokenFor(string $email): TokenInterface
    {
        $user = new SecurityUser(new UserView(
            id: 'user-id',
            hashedPassword: 'hash',
            contactInfo: 'contact',
            email: $email,
            groupId: 'group-id',
        ));
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
