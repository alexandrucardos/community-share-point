<?php

declare(strict_types=1);

namespace App\Domain\Item\Exception;

final class ItemNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct('Item not found.');
    }
}
