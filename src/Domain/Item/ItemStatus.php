<?php

declare(strict_types=1);

namespace App\Domain\Item;

enum ItemStatus: string
{
    case Available = 'Available';
    case Deleted = 'Deleted';
    case Reserved = 'Reserved';
}
