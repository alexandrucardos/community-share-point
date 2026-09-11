<?php

declare(strict_types=1);

namespace App\Service\RequestLog;

/**
 * Filter for the request/response log listing.
 *
 * All criteria are conjunctive; `null` means "no restriction".
 */
final class RequestLogFilter
{
    /** Values accepted for the entry-type criterion. */
    public const TYPES = ['request', 'response'];

    public function __construct(
        /** 'request' or 'response'; null = both. */
        public readonly ?string $type = null,
        /** Exact HTTP method (e.g. GET, POST); null = any. */
        public readonly ?string $method = null,
        /** Route name substring, case-insensitive; null = any. */
        public readonly ?string $route = null,
        /** Exact HTTP status code (404, 302, ...); null = any. */
        public readonly ?int $status = null,
        /** Case-insensitive substring matched against URI, route and payload values; null = any. */
        public readonly ?string $search = null,
    ) {
    }

    /**
     * True when no criterion is active.
     */
    public function isEmpty(): bool
    {
        return $this->type === null
            && $this->method === null
            && $this->route === null
            && $this->status === null
            && $this->search === null;
    }
}
