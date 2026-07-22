<?php

namespace App\Domain\DTO;

use App\Domain\ValueObject\Constraint;

final readonly class ConstraintDto
{
    public function __construct(
        public Constraint $constraint,
        public array $properties = [],
    ){
    }
}
