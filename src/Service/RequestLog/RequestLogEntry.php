<?php

declare(strict_types=1);

namespace App\Service\RequestLog;

/**
 * A request or response entry adapted from the persisted HTTP audit record.
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
        /** Request or response payload data. */
        public readonly array $payload,
        /** HTTP status for 'response' entries; null for 'request'. */
        public readonly ?int $status,
        public readonly ?string $contentType,
        public readonly ?int $contentLength,
        public readonly ?int $durationMs,
    ) {
    }

    /**
     * Same entry with a different payload, used to shorten payloads for display.
     *
     * @param array<array-key, mixed> $payload
     */
    public function withPayload(array $payload): self
    {
        return new self(
            timestamp: $this->timestamp,
            type: $this->type,
            method: $this->method,
            route: $this->route,
            uri: $this->uri,
            ip: $this->ip,
            payload: $payload,
            status: $this->status,
            contentType: $this->contentType,
            contentLength: $this->contentLength,
            durationMs: $this->durationMs,
        );
    }
}
