<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\RequestLog\DatabaseRequestLogReader;
use App\Service\RequestLog\RequestLogFilter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Lists request/response log entries from the `request_response_log` database table.
 *
 * Intentionally shows everything (all groups/users) — a diagnostic page,
 * currently open to ROLE_GUEST ("for now", per project decision).
 */
final class HttpResponseLogController extends AbstractController
{
    private const PAGE_SIZE = 10;

    #[Route('/group/{uuid}/request-log', name: 'request_response_log', methods: ['GET'], requirements: ['uuid' => GroupRoute::UUID])]
    #[IsGranted('ROLE_GUEST')]
    public function __invoke(
        string $uuid,
        DatabaseRequestLogReader $reader,
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

        // The reader pages the log in the database; the paginator is given the
        // page it already selected, and the real total so the widget and the
        // header can report it.
        $page = max(1, $page);
        $pageResult = $reader->readPage($filter, $page, self::PAGE_SIZE);
        $pagination = $paginator->paginate($pageResult->entries, $page, self::PAGE_SIZE);
        $pagination->setTotalItemCount($pageResult->totalItemCount);

        return $this->render('request_response_log/index.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'types' => RequestLogFilter::TYPES,
        ]);
    }
}
