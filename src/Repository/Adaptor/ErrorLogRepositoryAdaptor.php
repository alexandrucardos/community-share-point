<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Application\ErrorLog\ErrorLogQueryRepositoryInterface;
use App\Entity\ErrorLog;
use App\Repository\ErrorLogRepository;

/**
 * Reads the persisted error log for the ListErrors application handler.
 *
 * The rows are returned as plain arrays because the application contract has no
 * view object of its own; the keys match what templates/error_log/index.html.twig
 * renders.
 */
final class ErrorLogRepositoryAdaptor implements ErrorLogQueryRepositoryInterface
{
    /** Upper bound for one page of the log, which only ever grows. */
    private const PAGE_SIZE = 200;

    public function __construct(
        private readonly ErrorLogRepository $errorLogRepository,
    ) {
    }

    public function findAll(): array
    {
        return array_map(
            static fn(ErrorLog $record): array => [
                'message' => $record->message,
                'code' => $record->code,
                'trace' => $record->trace,
                'created_at' => $record->createdAt->format('Y-m-d H:i:s'),
            ],
            $this->errorLogRepository->findNewest(self::PAGE_SIZE),
        );
    }
}
