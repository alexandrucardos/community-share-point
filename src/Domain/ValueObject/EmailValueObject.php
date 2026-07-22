<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use App\Domain\DTO\ConstraintDto;

final readonly class EmailValueObject
{
    private function __construct(
        private ValidatorInterface $validator
    )
    {
    }

    public function validate(mixed $value): void
    {
        $constraints = [
            new ConstraintDto(Constraint::NotNull),
            new ConstraintDto(Constraint::NotBlank),
            new ConstraintDto(Constraint::Email),
            new ConstraintDto(Constraint::Length, ['max' => 100])
        ];

        $this->validator->validate(
            $value,
            $constraints
        );

    }
}
