<?php

namespace App\Repository\Adaptor;

use App\Application\ErrorLog\ErrorLogQueryRepositoryInterface;

class ErrorLogRepositoryAdaptorMock implements ErrorLogQueryRepositoryInterface
{

    public function findAll(): array
    {
        return [
            [
                'message' => 'error message',
                'code' => 404,
                'trace' => 'trace message',
                'created_at' => '2026-01-01 00:00:00',
            ],
            [
                'message' => 'error message',
                'code' => 500,
                'trace' => 'trace message',
                'created_at' => '2026-01-01 00:00:00',
            ],
            [
                'message' => 'error message2',
                'code' => 500,
                'trace' => 'trace message',
                'created_at' => '2026-01-01 00:00:00',
            ]
        ];
    }
}