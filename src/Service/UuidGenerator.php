<?php

namespace App\Service;

use App\Domain\UuidInterface;
use Symfony\Component\Uid\Uuid;

class UuidGenerator implements UuidInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
