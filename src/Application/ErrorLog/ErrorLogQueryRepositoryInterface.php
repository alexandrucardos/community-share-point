<?php

namespace App\Application\ErrorLog;

interface ErrorLogQueryRepositoryInterface
{
    public function findAll(): array;
}