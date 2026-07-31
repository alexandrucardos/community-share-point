<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use App\Domain\ValueObject\DTO\ConstraintDto;

final class PasswordValueObject
{
    public readonly mixed $value;

    public function __construct(
        private readonly ValidatorInterface $validator
    )
    {
    }

    public function __invoke(mixed $value): self
    {
        $constraints = [
            new ConstraintDto(Constraint::NotBlank),
            new ConstraintDto(Constraint::Length,
                [
                    Constraint::LENGTH_MIN => 8,
                    Constraint::LENGTH_MIN_MSG => 'user.password.min_length'
                ]
            )
        ];

        $this->validator->validate(
            $value,
            $constraints
        );

        $this->value = $value;

        return $this;
    }
}
