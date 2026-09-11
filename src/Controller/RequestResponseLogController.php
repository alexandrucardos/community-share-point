<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\RequestLog\PayloadLogFileReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Lists request/response log entries from the `request_payload` channel.
 *
 * Intentionally shows everything (all groups/users) — a diagnostic page,
 * currently open to ROLE_GUEST ("for now", per project decision).
 */
final class RequestResponseLogController extends AbstractController
{
    private const PAGE_SIZE = 200;

    #[Route('/group/{uuid}/request-log', name: 'request_response_log', methods: ['GET'], requirements: ['uuid' => GroupRoute::UUID])]
    #[IsGranted('ROLE_GUEST')]
    public function __invoke(string $uuid, PayloadLogFileReader $reader, #[MapQueryParameter] int $page = 1): Response
    {
        $allEntries = $reader->readAll();
        $total = \count($allEntries);

        $entries = \array_slice($allEntries, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE);

        return $this->render('request_response_log/index.html.twig', [
            'entries' => $entries,
            'total' => $total,
            'page' => $page,
            'pageSize' => self::PAGE_SIZE,
        ]);
    }
}
