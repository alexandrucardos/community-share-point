<?php

declare(strict_types=1);

namespace App\Service\RequestLog;

/**
 * A single request/response line pair parsed from the monolog
 * `request_payload` channel log (var/log/payload-YYYY-MM-DD.log).
 */
final class RequestLogEntry
{
    public function __construct(
        public readonly \DateTimeImmutable $timestamp,
        /** 'request' or 'response'. */
        public readonly string $type,
        public readonly string $method,
        /** Route name, or null when the request did not match a route (404s). */
        public readonly ?string $route,
        public readonly string $uri,
        public readonly ?string $ip,
        /** Raw payload array for 'request' entries; empty for 'response'. */
        public readonly array $payload,
        /** HTTP status for 'response' entries; null for 'request'. */
        public readonly ?int $status,
        public readonly ?string $contentType,
        public readonly ?int $contentLength,
        public readonly ?int $durationMs,
    ) {
    }
}
