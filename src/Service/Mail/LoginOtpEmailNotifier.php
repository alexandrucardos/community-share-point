<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Application\User\LoginOtpNotifierInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Delivers login codes through whatever transport MAILER_DSN points at.
 */
final class LoginOtpEmailNotifier implements LoginOtpNotifierInterface
{
    public function __construct(
        private readonly MailerInterface       $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string                $otpMailFrom,
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
                'verifyUrl' => $this->verifyUrl($code),
            ]);

        $this->mailer->send($message);
    }

    /**
     * Deep link to the code form with the code prefilled.
     *
     * The group {uuid} comes from the router context of the request that asked
     * for the code (see GroupUuidRouteContextListener). Without it — e.g. a
     * console context — the mail is sent without the link.
     */
    private function verifyUrl(string $code): ?string
    {
        try {
            return $this->urlGenerator->generate(
                'app_login_verify',
                ['code' => $code],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        } catch (RoutingExceptionInterface) {
            return null;
        }
    }
}
