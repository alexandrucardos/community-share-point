<?php

namespace App\Application\ErrorLog\ListErrors;

use App\Application\ErrorLog\ErrorLogQueryRepositoryInterface;

class ListErrorsHandler
{
    public function __construct(
        private readonly ErrorLogQueryRepositoryInterface $errorLogQueryRepository,
    )
    {
    }

    public function handle(ListErrorsQuery $query): array
    {
        return $this->errorLogQueryRepository->findAll();
    }
}