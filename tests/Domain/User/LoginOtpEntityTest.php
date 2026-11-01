<?php

declare(strict_types=1);

namespace App\Tests\Domain\User;

use App\Domain\User\LoginOtpEntity;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class LoginOtpEntityTest extends TestCase
{
    private const ID = '6f9c2f2e-1c3b-4d5e-8f7a-9b8c7d6e5f4a';
    private const GROUP_ID = '1f2e3d4c-5b6a-7f8e-9d0c-1b2a3f4e5d6c';
    private const EMAIL = 'john.doe@example.com';

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    public function testIssueHashesTheCodeAndOnlyTheClearCodeMatches(): void
    {
        $otp = $this->issue('123456');

        self::assertNotSame('123456', $otp->getHashedCode());
        self::assertTrue(password_verify('123456', $otp->getHashedCode()));
        self::assertTrue($otp->matches('123456', new \DateTimeImmutable()));
        self::assertFalse($otp->matches('000000', new \DateTimeImmutable()));
    }

    public function testIssueExpiresTheCodeAfterTenMinutes(): void
    {
        $issuedAt = new \DateTimeImmutable();

        $otp = $this->issue('123456', $issuedAt);

        $lifetimeMinutes = ($otp->getExpiresAt()->getTimestamp() - $issuedAt->getTimestamp()) / 60;

        self::assertSame(10, $lifetimeMinutes);
        self::assertFalse($otp->isExpired($issuedAt));
    }

    public function testAnExpiredCodeNoLongerMatches(): void
    {
        $issuedAt = new \DateTimeImmutable('-11 minutes');

        $otp = $this->issue('123456', $issuedAt);

        self::assertTrue($otp->isExpired(new \DateTimeImmutable()));
        self::assertFalse($otp->matches('123456', new \DateTimeImmutable()));
    }

    public function testMarkUsedMakesTheCodeUnusable(): void
    {
        $otp = $this->issue('123456');
        $usedAt = new \DateTimeImmutable();

        $otp->markUsed($usedAt);

        self::assertTrue($otp->isUsed());
        self::assertSame($usedAt, $otp->getUsedAt());
        self::assertFalse($otp->matches('123456', $usedAt));
    }

    public function testTheFirstConsumptionStampsTheCode(): void
    {
        $otp = $this->issue('123456');
        $firstUse = new \DateTimeImmutable();

        $otp->markUsed($firstUse);
        $otp->markUsed($firstUse->modify('+1 minute'));

        self::assertSame($firstUse, $otp->getUsedAt());
    }

    public function testRestoreRebuildsAStoredCode(): void
    {
        $expiresAt = new \DateTimeImmutable('+5 minutes');

        $otp = LoginOtpEntity::restore(
            $this->uuid(self::ID),
            $this->email(self::EMAIL),
            $this->uuid(self::GROUP_ID),
            password_hash('123456', PASSWORD_DEFAULT),
            $expiresAt,
            null,
        );

        self::assertSame(self::ID, $otp->getId()->value);
        self::assertSame(self::EMAIL, $otp->getEmail()->value);
        self::assertSame(self::GROUP_ID, $otp->getGroupId()->value);
        self::assertSame($expiresAt, $otp->getExpiresAt());
        self::assertFalse($otp->isUsed());
        self::assertTrue($otp->matches('123456', new \DateTimeImmutable()));
    }

    private function issue(string $code, ?\DateTimeImmutable $issuedAt = null): LoginOtpEntity
    {
        return LoginOtpEntity::issue(
            $this->uuid(self::ID),
            $this->email(self::EMAIL),
            $this->uuid(self::GROUP_ID),
            $code,
            $issuedAt ?? new \DateTimeImmutable(),
        );
    }

    private function uuid(string $value): UuidValueObject
    {
        return (new UuidValueObject($this->validator))($value);
    }

    private function email(string $value): EmailValueObject
    {
        return (new EmailValueObject($this->validator))($value);
    }
}
