<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ErrorLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One row per uncaught exception, written during kernel.exception.
 *
 * Like the other log records this is infrastructure-only, append-only data: it
 * is written for later inspection and never read back to drive application
 * behaviour.
 */
#[ORM\Entity(repositoryClass: ErrorLogRepository::class)]
#[ORM\Table(name: 'error_log')]
#[ORM\Index(name: 'idx_error_log_created_at', fields: ['createdAt'])]
class ErrorLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(name: 'exception_class', type: 'string')]
    public string $exceptionClass;

    #[ORM\Column(type: 'text')]
    public string $message;

    /** HTTP status for HTTP exceptions, otherwise the exception's own code. */
    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $code = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $file = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $line = null;

    #[ORM\Column(type: 'text')]
    public string $trace;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;
}
