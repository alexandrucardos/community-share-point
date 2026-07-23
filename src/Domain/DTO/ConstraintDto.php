<?php

namespace App\Domain\DTO;

use App\Domain\ValueObject\Constraint;

final class ConstraintDto
{
    public function __construct(
        public readonly Constraint $constraint,
        public readonly array $properties = [],
    ){
    }
}
