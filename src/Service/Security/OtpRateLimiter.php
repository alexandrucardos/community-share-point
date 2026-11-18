<?php

declare(strict_types=1);

namespace App\Service\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class OtpRateLimiter
{
    public function __construct(
        #[Autowire(service: 'limiter.otp_request_ip')]
        private readonly RateLimiterFactory $requestIpLimiter,
        #[Autowire(service: 'limiter.otp_request_account')]
        private readonly RateLimiterFactory $requestAccountLimiter,
        #[Autowire(service: 'limiter.otp_verify_ip')]
        private readonly RateLimiterFactory $verifyIpLimiter,
        #[Autowire(service: 'limiter.otp_verify_account')]
        private readonly RateLimiterFactory $verifyAccountLimiter,
    ) {
    }

    public function allowRequest(string $email, string $groupId, ?string $ip): bool
    {
        return $this->consumeBoth(
            $this->requestIpLimiter,
            $this->requestAccountLimiter,
            $this->ipKey($ip),
            $this->accountKey($email, $groupId),
        );
    }

    public function allowVerification(string $email, string $groupId, ?string $ip): bool
    {
        return $this->consumeBoth(
            $this->verifyIpLimiter,
            $this->verifyAccountLimiter,
            $this->ipKey($ip),
            $this->accountKey($email, $groupId),
        );
    }

    private function consumeBoth(
        RateLimiterFactory $ipLimiter,
        RateLimiterFactory $accountLimiter,
        string $ipKey,
        string $accountKey,
    ): bool {
        if (!$ipLimiter->create($ipKey)->consume()->isAccepted()) {
            return false;
        }

        return $accountLimiter->create($accountKey)->consume()->isAccepted();
    }

    private function ipKey(?string $ip): string
    {
        return hash('sha256', $ip ?? 'unknown');
    }

    private function accountKey(string $email, string $groupId): string
    {
        return hash('sha256', strtolower(trim($email)).'|'.$groupId);
    }
}
