<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;

/**
 * A one-shot login code belonging to a user account.
 *
 * Part of the User aggregate: it has no identity of its own outside the
 * (email, group) pair it was issued for. The clear code is never kept — only
 * its hash — and it stops being usable once consumed or after its lifetime.
 */
final class LoginOtpEntity
{
    private const LIFETIME_MINUTES = 10;

    private string $hashedCode;
    private \DateTimeImmutable $expiresAt;
    private ?\DateTimeImmutable $usedAt = null;

    private function __construct(
        private readonly UuidValueObject  $id,
        private readonly EmailValueObject $email,
        private readonly UuidValueObject  $groupId,
    )
    {
    }

    public static function issue(
        UuidValueObject    $id,
        EmailValueObject   $email,
        UuidValueObject    $groupId,
        string             $code,
        \DateTimeImmutable $issuedAt,
    ): self
    {
        $otp = new self($id, $email, $groupId);
        $otp->hashedCode = password_hash($code, PASSWORD_DEFAULT);
        $otp->expiresAt = $issuedAt->modify(sprintf('+%d minutes', self::LIFETIME_MINUTES));

        return $otp;
    }

    public static function restore(
        UuidValueObject     $id,
        EmailValueObject    $email,
        UuidValueObject     $groupId,
        string              $hashedCode,
        \DateTimeImmutable  $expiresAt,
        ?\DateTimeImmutable $usedAt,
    ): self
    {
        $otp = new self($id, $email, $groupId);
        $otp->hashedCode = $hashedCode;
        $otp->expiresAt = $expiresAt;
        $otp->usedAt = $usedAt;

        return $otp;
    }

    public function matches(string $code, \DateTimeImmutable $now): bool
    {
        if ($this->isUsed() || $this->isExpired($now)) {
            return false;
        }

        return password_verify($code, $this->hashedCode);
    }

    public function markUsed(\DateTimeImmutable $usedAt): void
    {
        $this->usedAt ??= $usedAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function getId(): UuidValueObject
    {
        return $this->id;
    }

    public function getEmail(): EmailValueObject
    {
        return $this->email;
    }

    public function getGroupId(): UuidValueObject
    {
        return $this->groupId;
    }

    public function getHashedCode(): string
    {
        return $this->hashedCode;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }
}
