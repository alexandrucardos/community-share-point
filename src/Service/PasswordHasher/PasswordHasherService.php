<?php

declare(strict_types=1);

namespace App\Service\PasswordHasher;

use App\Domain\User\PasswordHasherInterface;
use App\Domain\ValueObject\PasswordValueObject;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class PasswordHasherService implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function hash(PasswordValueObject $passwordValueObject): string
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(SecurityUser::class)
            ->hash($passwordValueObject->value);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(SecurityUser::class)
            ->verify($hashedPassword, $plainPassword);
    }
}
