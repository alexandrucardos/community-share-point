<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\ErrorLog\ListErrors\ListErrorsHandler;
use App\Application\ErrorLog\ListErrors\ListErrorsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ErrorLogController extends AbstractController
{
    #[Route('/error-log', name: 'error_log_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(ListErrorsHandler $listErrorsHandler): Response
    {
        return $this->render('error_log/index.html.twig', [
            'errors' => $listErrorsHandler->handle(new ListErrorsQuery()),
        ]);
    }
}
