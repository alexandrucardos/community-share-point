<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs the outcome (status, size, timing) of every main request.
 *
 * Runs at priority -128, i.e. after framework listeners, so the logged status
 * code is as close to final as possible. Exception responses (404/500) still
 * pass through here, so error outcomes are logged too. Sub-requests are
 * skipped to avoid duplicated entries.
 *
 * Writes to the same `request_payload` channel as RequestPayloadSubscriber,
 * so each request/response pair sits together in payload.log.
 */
final class ResponsePayloadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $requestPayloadLogger,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $startedAt = $request->server->get('REQUEST_TIME_FLOAT');
        $durationMs = is_numeric($startedAt)
            ? (int) round((microtime(true) - (float) $startedAt) * 1000)
            : null;

        $this->requestPayloadLogger->info('Response.', [
            'method' => $request->getMethod(),
            'route' => $request->attributes->get('_route'),
            'uri' => $request->getRequestUri(),
            'ip' => $request->getClientIp(),
            'status' => $response->getStatusCode(),
            'content_type' => $response->headers->get('Content-Type'),
            'content_length' => $response->headers->get('Content-Length')
                ?? strlen($response->getContent() ?? ''),
            'duration_ms' => $durationMs,
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onKernelResponse', -128]];
    }
}
