<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Makes the current request's group {uuid} an ambient routing parameter.
 *
 * Every page lives under `/group/{uuid}/...`, so seeding the router's
 * RequestContext with that uuid lets `path()`/`generateUrl()` auto-fill it the
 * same way `_locale` works — callers only pass their non-uuid arguments.
 *
 * Runs after RouterListener (priority 32, which populates the route attributes)
 * and before the firewall (priority 8), so the context is ready for both
 * templates/controllers and the security entry point.
 */
final class GroupUuidRouteContextListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // The error log is app-wide and has no group {uuid}.
        if ($event->getRequest()->attributes->get('_route') === 'error_log_index') {
            return;
        }

        $uuid = $event->getRequest()->attributes->get('uuid');

        if (is_string($uuid) && $uuid !== '') {
            $this->router->getContext()->setParameter('uuid', $uuid);
        }
    }
}
