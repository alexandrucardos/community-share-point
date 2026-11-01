<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Domain\UuidInterface;
use App\Entity\ErrorLog;
use App\Repository\ErrorLogRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Persists every uncaught exception of a main request to the `error_log` table.
 *
 * Runs after the other exception listeners (negative priority) so the recorded
 * exception is the one the client will actually receive, e.g. the 404 produced
 * from a missing route parameter. Sub-requests are skipped because the error
 * controller renders inside one.
 *
 * A failure to write the entry never replaces the original exception: it is
 * reported through the logger instead.
 */
final class ErrorLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ErrorLogRepository $errorLogRepository,
        private readonly UuidInterface $uuid,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $throwable = $event->getThrowable();

        try {
            $record = new ErrorLog();
            $record->id = $this->uuid->generate();
            $record->exceptionClass = $throwable::class;
            $record->message = $throwable->getMessage();
            $record->code = $this->code($throwable);
            $record->file = $throwable->getFile();
            $record->line = $throwable->getLine();
            $record->trace = $throwable->getTraceAsString();
            $record->createdAt = new \DateTimeImmutable();

            $this->errorLogRepository->append($record);
        } catch (\Throwable $loggingFailure) {
            $this->logger->error('Could not persist the error log entry.', [
                'exception' => $loggingFailure,
                'original_exception' => $throwable,
            ]);
        }
    }

    private function code(\Throwable $throwable): ?int
    {
        if ($throwable instanceof HttpExceptionInterface) {
            return $throwable->getStatusCode();
        }

        $code = $throwable->getCode();

        return is_int($code) ? $code : null;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', -64]];
    }
}
