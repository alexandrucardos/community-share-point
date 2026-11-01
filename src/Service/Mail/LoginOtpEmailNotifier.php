<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Application\User\LoginOtpNotifierInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Delivers login codes through whatever transport MAILER_DSN points at.
 */
final class LoginOtpEmailNotifier implements LoginOtpNotifierInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string          $otpMailFrom,
    )
    {
    }

    public function send(string $email, string $code, \DateTimeImmutable $expiresAt): void
    {
        $message = (new TemplatedEmail())
            ->from($this->otpMailFrom)
            ->to($email)
            ->subject('Community Share Point login code')
            ->htmlTemplate('email/login_otp.html.twig')
            ->textTemplate('email/login_otp.txt.twig')
            ->context([
                'code' => $code,
                'expiresAt' => $expiresAt,
            ]);

        $this->mailer->send($message);
    }
}
