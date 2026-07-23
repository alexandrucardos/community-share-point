<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use App\Domain\DTO\ConstraintDto;

final class ItemDescriptionValueObject
{
    public readonly mixed $value;

    public function __construct(
        private readonly ValidatorInterface $validator
    )
    {
    }

    public function __invoke(mixed $value): void
    {
        $constraints = [
            new ConstraintDto(Constraint::NotBlank, [Constraint::NOT_BLANK_MSG => 'item.description.not_blank']),
            new ConstraintDto(Constraint::Length,
                [
                    Constraint::LENGTH_MAX => 500,
                    Constraint::LENGTH_MAX_MSG => 'item.description.max_length'
                ]
            )
        ];

        $this->validator->validate(
            $value,
            $constraints
        );

        $this->value = $value;
    }
}
