<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LoginOtpRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One row per issued login code.
 *
 * Rows are append-only apart from `usedAt`, which is stamped when the code is
 * consumed. The code itself is stored hashed (see
 * {@see \App\Domain\User\LoginOtpEntity}).
 */
#[ORM\Entity(repositoryClass: LoginOtpRepository::class)]
#[ORM\Table(name: 'login_otp')]
#[ORM\Index(name: 'idx_login_otp_lookup', fields: ['email', 'groupId'])]
class LoginOtp
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(type: 'string')]
    public string $email;

    #[ORM\Column(name: 'group_id', type: 'string')]
    public string $groupId;

    #[ORM\Column(name: 'hashed_code', type: 'string')]
    public string $hashedCode;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    /** Null until the code has been consumed. */
    #[ORM\Column(name: 'used_at', type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $usedAt = null;
}
