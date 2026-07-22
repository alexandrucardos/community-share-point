<?php

declare(strict_types=1);

namespace App\Domain\Item\Exception;

final class ItemAccessDeniedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('You do not have permission to modify this item.');
    }
}
