<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs the payload (query string + body) of every incoming main request.
 *
 * Runs at priority 16 — after RouterListener (32) so the matched `_route` is
 * available for context, but before the firewall (8): authenticators
 * short-circuit the login request with a redirect, so anything at lower
 * priority would never see those payloads. Sub-requests are skipped to avoid
 * duplicated entries.
 */
final class RequestPayloadSubscriber implements EventSubscriberInterface
{
    private const SENSITIVE_KEYS = [
        '_password',
        '_password_confirmation',
        '_csrf_token',
    ];

    private const DEFAULT_VALUE_FOR_SENSITIVE_KEYS = '***';

    public function __construct(
        private readonly LoggerInterface $requestPayloadLogger,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $payload = array_merge(
            $request->query->all(),
            $request->getPayload()->all(),
        );

        $this->requestPayloadLogger->info('Request.', [
            'method' => $request->getMethod(),
            'route' => $request->attributes->get('_route'),
            'uri' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
            'payload' => $this->maskSensitiveValues($payload),
        ]);
    }

    private function maskSensitiveValues(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $payload[$key] = self::DEFAULT_VALUE_FOR_SENSITIVE_KEYS;
            }
        }

        return $payload;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 16]];
    }
}
