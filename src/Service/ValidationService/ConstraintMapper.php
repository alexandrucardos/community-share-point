<?php

namespace App\Service\ValidationService;

use App\Domain\ValueObject\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidator;

final readonly class ConstraintMapper
{
    public static function fetchAssert(
        Constraint $constraint,
        ?array $properties,
    ): ConstraintValidator
    {
        match ($constraint){
            Constraint::NotNull => new NotNull(),
            Constraint::NotBlank => new NotBlank(),
            Constraint::Email => new Email(),
            Constraint::Length => new Length(
                min: $properties['min'] ?? null,
                max: $properties['max'] ?? null,
            ),
        };
    }
}
