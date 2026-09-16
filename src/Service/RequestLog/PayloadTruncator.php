<?php

declare(strict_types=1);

namespace App\Service\RequestLog;

/**
 * Shortens payloads for display.
 *
 * A single response body can be several megabytes (an HTML page, a file
 * download). Rendering those values verbatim makes the listing huge and keeps
 * the whole body in memory, so every payload is cut down to a character budget
 * before it reaches the template. The value keeps its own fields and keys; only
 * oversized values are shortened and marked.
 */
final class PayloadTruncator
{
    /** Characters kept per payload, markers included. */
    public const DEFAULT_BUDGET = 2000;

    /** What is left of a shortened string, with its original length. */
    private const STRING_MARKER = '…[%d chars]';

    /** Marks payload fields that were dropped once the budget ran out. */
    private const FIELDS_MARKER = '…[%d fields dropped]';

    /** Key of the marker standing in for the dropped fields. */
    private const DROPPED_KEY = '…';

    /** Rough cost attributed to a non-string scalar (number, bool, null). */
    private const SCALAR_COST = 8;

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    public function truncate(array $payload, int $budget = self::DEFAULT_BUDGET): array
    {
        $remaining = max(0, $budget);

        return $this->shorten($payload, $remaining);
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    private function shorten(array $payload, int &$remaining): array
    {
        $result = [];
        $dropped = 0;

        foreach ($payload as $key => $value) {
            $remaining -= \strlen((string) $key);

            if ($remaining <= 0) {
                ++$dropped;
                continue;
            }

            if (\is_array($value)) {
                $result[$key] = $this->shorten($value, $remaining);
                continue;
            }

            if (\is_string($value)) {
                if (\strlen($value) > $remaining) {
                    $result[$key] = substr($value, 0, $remaining).sprintf(self::STRING_MARKER, \strlen($value));
                    $remaining = 0;
                    continue;
                }

                $result[$key] = $value;
                $remaining -= \strlen($value);
                continue;
            }

            $result[$key] = $value;
            $remaining -= self::SCALAR_COST;
        }

        if ($dropped > 0) {
            $result[self::DROPPED_KEY] = sprintf(self::FIELDS_MARKER, $dropped);
        }

        return $result;
    }
}
