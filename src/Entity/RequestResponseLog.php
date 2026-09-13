<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RequestResponseLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One database row for a main HTTP request and its response.
 *
 * The row is created during kernel.response from both the request and response.
 * It is intentionally kept in infrastructure rather than the domain because it
 * is an operational audit record.
 */
#[ORM\Entity(repositoryClass: RequestResponseLogRepository::class)]
#[ORM\Table(name: 'request_response_log')]
#[ORM\Index(name: 'idx_request_response_log_method', fields: ['method'])]
#[ORM\Index(name: 'idx_request_response_log_status', fields: ['status'])]
class RequestResponseLog
{

    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(type: 'string')]
    public string $method;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $route = null;

    #[ORM\Column(type: 'text')]
    public string $uri;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $ip = null;

    /** Query-string and request-body data, with sensitive values masked. */
    #[ORM\Column(name: 'request_payload', type: 'json')]
    public array $requestPayload = [];

    /** Decoded JSON response data, or a body wrapper for non-JSON responses. */
    #[ORM\Column(name: 'response_payload', type: 'json', nullable: true)]
    public ?array $responsePayload = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $status = null;

    #[ORM\Column(name: 'content_type', type: 'string', nullable: true)]
    public ?string $contentType = null;

    #[ORM\Column(name: 'content_length', type: 'integer', nullable: true)]
    public ?int $contentLength = null;

    #[ORM\Column(name: 'duration_ms', type: 'integer', nullable: true)]
    public ?int $durationMs = null;

    #[ORM\Column(name: 'responded_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $respondedAt;
}
