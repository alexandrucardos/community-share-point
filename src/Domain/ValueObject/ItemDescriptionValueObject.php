<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use App\Domain\ValueObject\DTO\ConstraintDto;

final class ItemDescriptionValueObject
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        public readonly mixed $value
    )
    {
        $this->validate($value);
    }

    private function validate(mixed $value): void
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
    }
}
