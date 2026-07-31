<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use App\Domain\ValueObject\DTO\ConstraintDto;

final class ContactInfoValueObject
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
            new ConstraintDto(Constraint::NotBlank, [Constraint::NOT_BLANK_MSG => 'user.contact_info.not_blank']),
            new ConstraintDto(Constraint::Length,
                [
                    Constraint::LENGTH_MAX => 100,
                    Constraint::LENGTH_MAX_MSG => 'user.contact_info.max_length'
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

    public function __serialize(): array
    {
        return ['value' => $this->value];
    }

    public function __unserialize(array $data): void
    {
        $this->value = $data['value'];
    }
}
