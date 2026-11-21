<?php

declare(strict_types=1);

namespace App\Tests\Service\Security;

use App\Service\Security\OtpRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class OtpRateLimiterTest extends TestCase
{
    public function testRequestLimitIsEnforcedPerAccount(): void
    {
        $limiter = $this->createRateLimiter(requestIpLimit: 10, requestAccountLimit: 1);

        self::assertTrue($limiter->allowRequest('user@example.com', 'group-1', '192.0.2.1'));
        self::assertFalse($limiter->allowRequest('user@example.com', 'group-1', '192.0.2.2'));
        self::assertTrue($limiter->allowRequest('user@example.com', 'group-2', '192.0.2.2'));
    }

    public function testRequestLimitIsEnforcedPerIp(): void
    {
        $limiter = $this->createRateLimiter(requestIpLimit: 2, requestAccountLimit: 10);

        self::assertTrue($limiter->allowRequest('one@example.com', 'group-1', '192.0.2.1'));
        self::assertTrue($limiter->allowRequest('two@example.com', 'group-1', '192.0.2.1'));
        self::assertFalse($limiter->allowRequest('three@example.com', 'group-1', '192.0.2.1'));
    }

    public function testVerificationLimitIsIndependentFromRequestLimit(): void
    {
        $limiter = $this->createRateLimiter(verifyIpLimit: 10, verifyAccountLimit: 1);

        self::assertTrue($limiter->allowRequest('user@example.com', 'group-1', '192.0.2.1'));
        self::assertTrue($limiter->allowVerification('user@example.com', 'group-1', '192.0.2.1'));
        self::assertFalse($limiter->allowVerification('user@example.com', 'group-1', '192.0.2.1'));
    }

    private function createRateLimiter(
        int $requestIpLimit = 10,
        int $requestAccountLimit = 3,
        int $verifyIpLimit = 30,
        int $verifyAccountLimit = 5,
    ): OtpRateLimiter {
        $requestIp = $this->factory('request-ip', $requestIpLimit);
        $requestAccount = $this->factory('request-account', $requestAccountLimit);
        $verifyIp = $this->factory('verify-ip', $verifyIpLimit);
        $verifyAccount = $this->factory('verify-account', $verifyAccountLimit);

        return new OtpRateLimiter($requestIp, $requestAccount, $verifyIp, $verifyAccount);
    }

    private function factory(string $id, int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory([
            'id' => $id,
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => '1 hour',
        ], new InMemoryStorage());
    }
}
