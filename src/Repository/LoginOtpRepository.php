<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoginOtp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for {@see LoginOtp} rows.
 *
 * @extends ServiceEntityRepository<LoginOtp>
 */
final class LoginOtpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginOtp::class);
    }

    /**
     * Insert the row, or overwrite the consumption stamp of an existing one.
     *
     * Codes are otherwise immutable once issued, so nothing else is copied
     * over.
     */
    public function save(LoginOtp $record): void
    {
        $manager = $this->getEntityManager();
        $existing = $this->find($record->id);

        if ($existing === null) {
            $manager->persist($record);
        } else {
            $existing->usedAt = $record->usedAt;
        }

        $manager->flush();
    }

    /**
     * Newest unused code that has not expired yet.
     */
    public function findLatestValid(
        string $email,
        string $groupId,
        \DateTimeImmutable $now,
    ): ?LoginOtp {
        return $this->createQueryBuilder('otp')
            ->andWhere('otp.email = :email')
            ->andWhere('otp.groupId = :groupId')
            ->andWhere('otp.usedAt IS NULL')
            ->andWhere('otp.expiresAt > :now')
            ->setParameter('email', $email)
            ->setParameter('groupId', $groupId)
            ->setParameter('now', $now)
            ->orderBy('otp.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function markPendingUsed(string $email, string $groupId, \DateTimeImmutable $usedAt): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update(LoginOtp::class, 'otp')
            ->set('otp.usedAt', ':usedAt')
            ->where('otp.email = :email')
            ->andWhere('otp.groupId = :groupId')
            ->andWhere('otp.usedAt IS NULL')
            ->setParameter('usedAt', $usedAt)
            ->setParameter('email', $email)
            ->setParameter('groupId', $groupId)
            ->getQuery()
            ->execute();
    }
}
