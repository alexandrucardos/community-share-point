<?php

namespace App\Domain\ValueObject;

interface ValidatorInterface
{
    public function validate(mixed $value, array $constraintsDto):void;
}
