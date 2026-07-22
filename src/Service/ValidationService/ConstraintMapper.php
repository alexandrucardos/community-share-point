<?php

namespace App\Service\ValidationService;

use App\Domain\ValueObject\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraint as FrameworkConstraint;

final readonly class ConstraintMapper
{
    public static function fetchAssert(
        Constraint $constraint,
        ?array $properties,
    ): FrameworkConstraint
    {
        return match ($constraint){
            Constraint::NotNull => new NotNull(),
            Constraint::NotBlank => new NotBlank(
                message: $properties[Constraint::NOT_BLANK_MSG] ?? null,
            ),
            Constraint::Email => new Email(),
            Constraint::Length => new Length(
                min: $properties[Constraint::LENGTH_MIN] ?? null,
                max: $properties[Constraint::LENGTH_MAX] ?? null,
                minMessage: $properties[Constraint::LENGTH_MIN_MSG] ?? null,
                maxMessage: $properties[Constraint::LENGTH_MAX_MSG] ?? null,
            ),
        };
    }
}
