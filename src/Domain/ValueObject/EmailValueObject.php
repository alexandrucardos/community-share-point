<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use App\Domain\DTO\ConstraintDto;

final readonly class EmailValueObject
{
    public mixed $value;

    public function __construct(
        private ValidatorInterface $validator
    )
    {
    }

    public function __invoke(mixed $value): void
    {
        $constraints = [
            new ConstraintDto(Constraint::NotNull),
            new ConstraintDto(Constraint::NotBlank),
            new ConstraintDto(Constraint::Email),
            new ConstraintDto(Constraint::Length,
                [
                    Constraint::LENGTH_MAX => 100,
                    Constraint::LENGTH_MAX_MSG => 'user.email.max_length'
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
