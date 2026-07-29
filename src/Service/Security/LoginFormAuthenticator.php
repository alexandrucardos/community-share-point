<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Application\LoadUser\LoadUserDto;
use App\Application\LoadUserByEmailAndGroup\LoadUserByEmailAndGroupDto;
use App\Application\LoadUserByEmailAndGroup\LoadUserByEmailAndGroupHandler;
use App\Application\LoadUserByEmailAndGroup\LoadUserByEmailAndGroupQuery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Authenticates the login form by (email, group_id).
 *
 * The email is submitted in the form body while the group id is the `{uuid}`
 * segment of the `/group/{uuid}/login` route. Since an email is only unique
 * within a group, both are required to resolve a user. The session identity
 * remains the user's id, so refresh/{@see AppUserProvider} stay id-based.
 */
final class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly LoadUserByEmailAndGroupHandler $loadUserByEmailAndGroup,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
        // Match by route name rather than the parent's char-for-char path
        // compare, which is fragile against the {uuid} segment's casing.
        return $request->isMethod('POST') && $request->attributes->get('_route') === 'app_login';
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->request->get('_username', '');
        $password = (string) $request->request->get('_password', '');
        $groupId = $this->groupId($request);
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        }

        $sessionUser = $this->toSessionUser($this->loadUser($email, $groupId));

        return new Passport(
            new UserBadge($sessionUser->id, static fn (): SecurityUser => new SecurityUser($sessionUser)),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $groupId = $user instanceof SecurityUser
            ? $user->getLoadUserDto()->groupId
            : $this->groupId($request);

        return new RedirectResponse(
            $this->urlGenerator->generate('household_items_index', ['uuid' => $groupId]),
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        $groupId = $this->groupId($request);

        if ($groupId === '') {
            return '/';
        }

        return $this->urlGenerator->generate('app_login', ['uuid' => $groupId]);
    }

    private function groupId(Request $request): string
    {
        return (string) $request->attributes->get('uuid', '');
    }

    private function loadUser(string $email, string $groupId): LoadUserByEmailAndGroupDto
    {
        try {
            $user = $this->loadUserByEmailAndGroup->query(
                new LoadUserByEmailAndGroupQuery($email, $groupId),
            );
        } catch (\InvalidArgumentException) {
            // A malformed email or group id fails validation inside the query;
            // treat it as bad credentials rather than a server error.
            $user = null;
        }

        if ($user === null) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        return $user;
    }

    /**
     * Adapt the login read model into the shared session model that
     * {@see SecurityUser} (and the id-based refresh path) speak in.
     */
    private function toSessionUser(LoadUserByEmailAndGroupDto $user): LoadUserDto
    {
        return new LoadUserDto(
            id: $user->id,
            hashedPassword: $user->hashedPassword,
            contactInfo: $user->contactInfo,
            email: $user->email,
            groupId: $user->groupId,
        );
    }
}
