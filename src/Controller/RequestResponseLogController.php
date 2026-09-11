<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\RequestLog\PayloadLogFileReader;
use App\Service\RequestLog\RequestLogFilter;
use Knp\Component\Pager\PaginatorInterface;
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
    public function __invoke(
        string $uuid,
        PayloadLogFileReader $reader,
        PaginatorInterface $paginator,
        #[MapQueryParameter] string $type = '',
        #[MapQueryParameter] string $method = '',
        #[MapQueryParameter] string $route = '',
        #[MapQueryParameter] string $status = '',
        #[MapQueryParameter] string $search = '',
        #[MapQueryParameter] int $page = 1,
    ): Response {
        $filter = new RequestLogFilter(
            type: $type !== '' ? $type : null,
            method: $method !== '' ? $method : null,
            route: $route !== '' ? $route : null,
            status: $status !== '' ? (int) $status : null,
            search: $search !== '' ? $search : null,
        );

        $pagination = $paginator->paginate(
            $reader->read($filter),
            $page,
            self::PAGE_SIZE,
        );

        return $this->render('request_response_log/index.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'types' => RequestLogFilter::TYPES,
        ]);
    }
}
