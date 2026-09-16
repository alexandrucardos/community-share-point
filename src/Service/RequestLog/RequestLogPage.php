<?php

declare(strict_types=1);

namespace App\Service\RequestLog;

/**
 * One page of request/response log entries.
 *
 * Entries are already truncated for display; `totalItemCount` counts every
 * entry matching the filter, not just the ones on this page.
 */
final class RequestLogPage
{
    /**
     * @param RequestLogEntry[] $entries
     */
    public function __construct(
        public readonly array $entries,
        public readonly int $totalItemCount,
    ) {
    }
}
