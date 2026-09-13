<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Domain\UuidInterface;
use App\Entity\RequestResponseLog;
use App\Repository\RequestResponseLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs and persists the complete outcome of every main request.
 *
 * The request is still available from the response event, so the complete
 * request/response record is created here and written only once per request.
 * Sub-requests are skipped to avoid duplicate entries.
 */
final class ResponsePayloadSubscriber implements EventSubscriberInterface
{
    private const SENSITIVE_KEYS = [
        '_password',
        '_password_confirmation',
        '_csrf_token',
    ];

    private const DEFAULT_VALUE_FOR_SENSITIVE_KEYS = '***';

    public function __construct(
        private readonly LoggerInterface $requestPayloadLogger,
        private readonly RequestResponseLogRepository $requestResponseLogRepository,
        private readonly UuidInterface $uuid,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestPayload = $this->maskSensitiveValues(array_merge(
            $request->query->all(),
            $request->getPayload()->all(),
        ));

        $record = new RequestResponseLog();
        $record->id = $this->uuid->generate();
        $record->method = $request->getMethod();
        $record->route = is_string($request->attributes->get('_route'))
            ? $request->attributes->get('_route')
            : null;
        $record->uri = $request->getRequestUri();
        $record->ip = $request->getClientIp();
        $record->requestPayload = $requestPayload;
        $record->status = $response->getStatusCode();
        $record->contentType = $response->headers->get('Content-Type');
        $record->contentLength = $response->headers->get('Content-Length') !== null
            ? (int) $response->headers->get('Content-Length')
            : strlen($response->getContent() ?? '');
        $record->durationMs = $this->durationMs($request);
        $record->responsePayload = $this->responsePayload($response);
        $record->respondedAt = new \DateTimeImmutable();

        $this->requestResponseLogRepository->append($record);

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
            'payload' => $requestPayload,
        ]);
    }

    /** @return array<string, mixed> */
    private function responsePayload(Response $response): array
    {
        $content = $response->getContent() ?? '';
        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return ['body' => $content];
    }

    private function durationMs(\Symfony\Component\HttpFoundation\Request $request): ?int
    {
        $startedAt = $request->server->get('REQUEST_TIME_FLOAT');

        return is_numeric($startedAt)
            ? (int) round((microtime(true) - (float) $startedAt) * 1000)
            : null;
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
        return [KernelEvents::RESPONSE => ['onKernelResponse', -128]];
    }
}
