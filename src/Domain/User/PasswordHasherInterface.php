<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\ValueObject\PasswordValueObject;

interface PasswordHasherInterface
{
    public function hash(PasswordValueObject $passwordValueObject): string;

    public function verify(string $hashedPassword, string $plainPassword): bool;
}
