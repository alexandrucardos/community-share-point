<?php

declare(strict_types=1);

namespace App\Service\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Redirects to the group login page after logout.
 *
 * `/logout` carries no {uuid}, so the default logout target (`app_login`) can
 * no longer be generated from the URL. The group is instead taken from the
 * still-present token's user, and used to build `/group/{uuid}/login`.
 */
final class LogoutRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('app_login', ['uuid' => $user->getUserView()->groupId]),
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => ['onLogout', 64]];
    }
}
