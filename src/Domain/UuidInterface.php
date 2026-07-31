<?php

namespace App\Domain;

interface UuidInterface
{
    public function generate(): string;
}
