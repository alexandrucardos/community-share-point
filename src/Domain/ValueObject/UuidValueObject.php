<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use App\Domain\DTO\ConstraintDto;

final class UuidValueObject
{
    public mixed $value;

    public function __construct(
        private readonly ValidatorInterface $validator
    )
    {
    }

    public function __invoke(mixed $value): self
    {
        $constraints = [
            new ConstraintDto(Constraint::Uuid),
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
