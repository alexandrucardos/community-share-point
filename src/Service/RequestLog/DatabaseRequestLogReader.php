<?php

declare(strict_types=1);

namespace App\Service\RequestLog;

use App\Entity\RequestResponseLog;
use App\Repository\RequestResponseLogRepository;

/**
 * Reads request/response audit records from the database and adapts each
 * paired record to the entries expected by the request-log page.
 *
 * The log grows on every request and its payloads can be large, so nothing is
 * read unbounded: pages are fetched with the criteria the database can apply,
 * and a payload search scans the log chunk by chunk, releasing each chunk
 * before moving on. Payload values are shortened for display on the way out.
 */
final class DatabaseRequestLogReader
{
    /**
     * Records fetched per query while scanning for a payload search.
     *
     * Small enough that a chunk of large payloads stays affordable in memory.
     */
    private const SCAN_CHUNK_SIZE = 50;

    public function __construct(
        private readonly RequestResponseLogRepository $repository,
        private readonly PayloadTruncator $truncator,
    ) {
    }

    /**
     * Every matching entry, newest first.
     *
     * Unbounded by nature: callers that render a listing must use {@see readPage()}.
     *
     * @return RequestLogEntry[]
     */
    public function read(RequestLogFilter $filter): array
    {
        return $this->collect($filter, 0, PHP_INT_MAX)['entries'];
    }

    /**
     * One page of matching entries, newest first, together with the total
     * number of matching entries.
     */
    public function readPage(RequestLogFilter $filter, int $page, int $perPage): RequestLogPage
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $entriesPerRow = self::entriesPerRow($filter);

        if ($entriesPerRow === 0) {
            return new RequestLogPage([], 0);
        }

        // Method, route and status live on the record itself, so without a
        // payload search the database can count the entries and hand back just
        // the wanted window. Entry offsets are translated into record offsets
        // because every record yields the same number of entries here.
        if ($filter->search === null) {
            $rowOffset = intdiv($offset, $entriesPerRow);
            $rowLimit = (int) ceil(($offset % $entriesPerRow + $perPage) / $entriesPerRow);

            $entries = [];
            $rows = $this->repository->findForLogPage(
                $filter->method,
                $filter->route,
                $filter->status,
                $rowOffset,
                $rowLimit,
            );

            foreach ($rows as $record) {
                foreach ($this->projectedEntries($record, $filter) as $entry) {
                    $entries[] = $entry;
                }
            }

            $rowCount = $this->repository->countForLog($filter->method, $filter->route, $filter->status);

            return new RequestLogPage(
                array_slice($entries, $offset % $entriesPerRow, $perPage),
                $rowCount * $entriesPerRow,
            );
        }

        $collected = $this->collect($filter, $offset, $perPage);

        return new RequestLogPage($collected['entries'], $collected['totalItemCount']);
    }

    /**
     * Walks matching records in chunks and collects a window of entries while
     * counting them all.
     *
     * @return array{entries: RequestLogEntry[], totalItemCount: int}
     */
    private function collect(RequestLogFilter $filter, int $offset, int $limit): array
    {
        $entries = [];
        $totalItemCount = 0;
        $skipped = $offset;

        for ($chunkOffset = 0; ; $chunkOffset += self::SCAN_CHUNK_SIZE) {
            $records = $this->repository->findForLogPage(
                $filter->method,
                $filter->route,
                $filter->status,
                $chunkOffset,
                self::SCAN_CHUNK_SIZE,
            );

            if ($records === []) {
                break;
            }

            foreach ($records as $record) {
                foreach ($this->entriesFor($record) as $entry) {
                    if (!self::matches($entry, $filter)) {
                        continue;
                    }

                    ++$totalItemCount;

                    if ($skipped > 0) {
                        --$skipped;
                        continue;
                    }

                    if (count($entries) < $limit) {
                        $entries[] = $this->forDisplay($entry);
                    }
                }
            }

            if (count($records) < self::SCAN_CHUNK_SIZE) {
                break;
            }

            $this->repository->clear();
        }

        return ['entries' => $entries, 'totalItemCount' => $totalItemCount];
    }

    /**
     * Entries of a record that the database could not rule out, still carrying
     * their full payloads so a payload search stays exact.
     *
     * @return RequestLogEntry[]
     */
    private function entriesFor(RequestResponseLog $record): array
    {
        return [$this->requestEntry($record), $this->responseEntry($record)];
    }

    /**
     * Entries of a record narrowed to what the filter displays, for the case
     * where the database already matched the record on its own columns.
     *
     * @return RequestLogEntry[]
     */
    private function projectedEntries(RequestResponseLog $record, RequestLogFilter $filter): array
    {
        $entries = array_filter(
            $this->entriesFor($record),
            static fn (RequestLogEntry $entry): bool => self::isProjected($entry, $filter),
        );

        return array_map($this->forDisplay(...), array_values($entries));
    }

    /**
     * How many entries of a matching record the filter displays.
     *
     * Only responses carry a status, so a status filter both selects records
     * and hides their request entry.
     */
    private static function entriesPerRow(RequestLogFilter $filter): int
    {
        if ($filter->status !== null) {
            return $filter->type === 'request' ? 0 : 1;
        }

        return $filter->type === null ? 2 : 1;
    }

    private static function isProjected(RequestLogEntry $entry, RequestLogFilter $filter): bool
    {
        if ($filter->type !== null && $entry->type !== $filter->type) {
            return false;
        }

        return $filter->status === null || $entry->type === 'response';
    }

    private static function matches(RequestLogEntry $entry, RequestLogFilter $filter): bool
    {
        if (!self::isProjected($entry, $filter)) {
            return false;
        }

        if ($filter->method !== null && strcasecmp($entry->method, $filter->method) !== 0) {
            return false;
        }

        if ($filter->route !== null
            && ($entry->route === null || stripos($entry->route, $filter->route) === false)) {
            return false;
        }

        if ($filter->status !== null && $entry->status !== $filter->status) {
            return false;
        }

        if ($filter->search !== null) {
            $haystacks = [$entry->uri, $entry->route];
            foreach ($entry->payload as $value) {
                if (is_scalar($value)) {
                    $haystacks[] = (string) $value;
                }
            }

            foreach ($haystacks as $haystack) {
                if ($haystack !== null && mb_stripos($haystack, $filter->search) !== false) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Bounds the payload a template has to render.
     */
    private function forDisplay(RequestLogEntry $entry): RequestLogEntry
    {
        if ($entry->payload === []) {
            return $entry;
        }

        return $entry->withPayload($this->truncator->truncate($entry->payload));
    }

    private function requestEntry(RequestResponseLog $record): RequestLogEntry
    {
        return new RequestLogEntry(
            timestamp: $record->respondedAt,
            type: 'request',
            method: $record->method,
            route: $record->route,
            uri: $record->uri,
            ip: $record->ip,
            payload: $record->requestPayload,
            status: null,
            contentType: null,
            contentLength: null,
            durationMs: null,
        );
    }

    private function responseEntry(RequestResponseLog $record): RequestLogEntry
    {
        return new RequestLogEntry(
            timestamp: $record->respondedAt,
            type: 'response',
            method: $record->method,
            route: $record->route,
            uri: $record->uri,
            ip: $record->ip,
            payload: $record->responsePayload ?? [],
            status: $record->status,
            contentType: $record->contentType,
            contentLength: $record->contentLength,
            durationMs: $record->durationMs,
        );
    }
}
