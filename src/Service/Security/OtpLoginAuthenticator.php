<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Application\User\GetUser\UserView;
use App\Application\User\GetUserByEmailAndGroup\GetUserByEmailAndGroupHandler;
use App\Application\User\GetUserByEmailAndGroup\GetUserByEmailAndGroupQuery;
use App\Application\User\GetUserByEmailAndGroup\UserCredentialsView;
use App\Application\User\VerifyLoginOtp\InvalidLoginOtpException;
use App\Application\User\VerifyLoginOtp\VerifyLoginOtpCommand;
use App\Application\User\VerifyLoginOtp\VerifyLoginOtpHandler;
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
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Authenticates the login-code form, replacing password authentication.
 *
 * The email is requested on `app_login` and kept in the session; this
 * authenticator only runs on the `app_login_verify` POST, where it resolves
 * the user by (email, group id) and consumes the emailed code.
 */
final class OtpLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    /** Session key holding the email a code was sent to. */
    public const SESSION_EMAIL = 'login_otp_email';

    private const CSRF_TOKEN_ID = 'authenticate_otp';

    public function __construct(
        private readonly GetUserByEmailAndGroupHandler $loadUserByEmailAndGroup,
        private readonly VerifyLoginOtpHandler $verifyLoginOtp,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $request->attributes->get('_route') === 'app_login_verify';
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->getSession()->get(self::SESSION_EMAIL, '');
        $code = trim((string) $request->request->get('_code', ''));
        $csrfToken = (string) $request->request->get('_csrf_token', '');
        $groupId = $this->groupId($request);

        if ($email === '') {
            throw new CustomUserMessageAuthenticationException('Your login code has expired, please request a new one.');
        }

        $sessionUser = $this->toSessionUser($this->loadUser($email, $groupId));

        return new Passport(
            new UserBadge($sessionUser->id, static fn (): SecurityUser => new SecurityUser($sessionUser)),
            new CustomCredentials(
                function (mixed $credentials) use ($email, $groupId): bool {
                    try {
                        $this->verifyLoginOtp->handle(new VerifyLoginOtpCommand(
                            email: $email,
                            groupId: $groupId,
                            code: (string) $credentials,
                        ));
                    } catch (InvalidLoginOtpException) {
                        throw new CustomUserMessageAuthenticationException(
                            'That login code is not valid or has expired.',
                        );
                    }

                    return true;
                },
                $code,
            ),
            [
                new CsrfTokenBadge(self::CSRF_TOKEN_ID, $csrfToken),
            ],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request->getSession()->remove(self::SESSION_EMAIL);

        $user = $token->getUser();
        $groupId = $user instanceof SecurityUser
            ? $user->getUserView()->groupId
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

        // Without a pending email the verify page cannot render, so start over.
        $route = $request->getSession()->get(self::SESSION_EMAIL) !== null
            ? 'app_login_verify'
            : 'app_login';

        $groupId = $this->groupId($request);

        if ($groupId === '') {
            return new RedirectResponse('/');
        }

        return new RedirectResponse($this->urlGenerator->generate($route, ['uuid' => $groupId]));
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

    private function loadUser(string $email, string $groupId): UserCredentialsView
    {
        try {
            $user = $this->loadUserByEmailAndGroup->query(
                new GetUserByEmailAndGroupQuery($email, $groupId),
            );
        } catch (\InvalidArgumentException) {
            $user = null;
        }

        if ($user === null) {
            throw new CustomUserMessageAuthenticationException('That login code is not valid or has expired.');
        }

        return $user;
    }

    /**
     * Adapt the login read model into the session model {@see SecurityUser}
     * and the id-based refresh path speak in.
     */
    private function toSessionUser(UserCredentialsView $user): UserView
    {
        return new UserView(
            id: $user->id,
            contactInfo: $user->contactInfo,
            email: $user->email,
            groupId: $user->groupId,
        );
    }
}
