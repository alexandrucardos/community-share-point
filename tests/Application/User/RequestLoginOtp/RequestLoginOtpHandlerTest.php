<?php

declare(strict_types=1);

namespace App\Tests\Application\User\RequestLoginOtp;

use App\Application\User\GetUserByEmailAndGroup\GetUserByEmailAndGroupHandler;
use App\Application\User\GetUserByEmailAndGroup\UserCredentialsView;
use App\Application\User\LoginOtpNotifierInterface;
use App\Application\User\RequestLoginOtp\RequestLoginOtpCommand;
use App\Application\User\RequestLoginOtp\RequestLoginOtpHandler;
use App\Application\User\UserQueryRepositoryInterface;
use App\Domain\User\LoginOtpEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class RequestLoginOtpHandlerTest extends TestCase
{
    private const GROUP_ID = '11111111-1111-4111-8111-111111111111';
    private const USER_ID = '22222222-2222-4222-8222-222222222222';
    private const OTP_ID = '33333333-3333-4333-8333-333333333333';

    private UserQueryRepositoryInterface&MockObject $userQueryRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private LoginOtpNotifierInterface&MockObject $notifier;
    private RequestLoginOtpHandler $handler;

    protected function setUp(): void
    {
        $this->userQueryRepository = $this->createMock(UserQueryRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->notifier = $this->createMock(LoginOtpNotifierInterface::class);

        $validator = new ValidatorService($this->createTranslator());

        $uuid = $this->createStub(UuidInterface::class);
        $uuid->method('generate')->willReturn(self::OTP_ID);

        $this->handler = new RequestLoginOtpHandler(
            new GetUserByEmailAndGroupHandler(
                $this->userQueryRepository,
                new EmailValueObject($validator),
                new UuidValueObject($validator),
            ),
            $this->userRepository,
            $this->notifier,
            $uuid,
            $validator,
        );
    }

    public function testHandleStoresAHashedCodeAndMailsTheClearOne(): void
    {
        $this->userQueryRepository->method('findByEmailAndGroupId')->willReturn($this->credentials());

        $stored = null;
        $sent = [];

        $this->userRepository->expects(self::once())
            ->method('invalidatePendingLoginOtps')
            ->with(
                self::isInstanceOf(EmailValueObject::class),
                self::isInstanceOf(UuidValueObject::class),
                self::isInstanceOf(\DateTimeImmutable::class),
            );

        $this->userRepository->expects(self::once())
            ->method('addLoginOtp')
            ->willReturnCallback(function (LoginOtpEntity $otp) use (&$stored): void {
                $stored = $otp;
            });

        $this->notifier->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (string $email, string $code, \DateTimeImmutable $expiresAt) use (&$sent): void {
                $sent = compact('email', 'code', 'expiresAt');
            });

        $this->handler->handle(new RequestLoginOtpCommand('john.doe@example.com', self::GROUP_ID));

        self::assertInstanceOf(LoginOtpEntity::class, $stored);
        self::assertSame(self::OTP_ID, $stored->getId()->value);
        self::assertSame('john.doe@example.com', $stored->getEmail()->value);
        self::assertSame(self::GROUP_ID, $stored->getGroupId()->value);
        self::assertSame('john.doe@example.com', $sent['email']);
        self::assertMatchesRegularExpression('/^\d{6}$/', $sent['code']);
        self::assertSame($sent['expiresAt'], $stored->getExpiresAt());
        self::assertTrue($stored->matches($sent['code'], new \DateTimeImmutable()), 'the stored hash must verify the mailed code');
        self::assertNotSame($sent['code'], $stored->getHashedCode(), 'the stored code must not be the clear one');
    }

    public function testHandleExpiresTheCodeAfterTenMinutes(): void
    {
        $this->userQueryRepository->method('findByEmailAndGroupId')->willReturn($this->credentials());

        $stored = null;

        $this->userRepository->method('addLoginOtp')
            ->willReturnCallback(function (LoginOtpEntity $otp) use (&$stored): void {
                $stored = $otp;
            });
        $this->notifier->method('send');

        $this->handler->handle(new RequestLoginOtpCommand('john.doe@example.com', self::GROUP_ID));

        self::assertInstanceOf(LoginOtpEntity::class, $stored);
        $lifetimeMinutes = ($stored->getExpiresAt()->getTimestamp() - time()) / 60;

        self::assertGreaterThan(9, $lifetimeMinutes);
        self::assertLessThanOrEqual(10, $lifetimeMinutes);
    }

    public function testHandleSendsNothingForAnUnknownEmailInTheGroup(): void
    {
        $this->userQueryRepository->method('findByEmailAndGroupId')->willReturn(null);

        $this->userRepository->expects(self::never())->method('addLoginOtp');
        $this->notifier->expects(self::never())->method('send');

        $this->handler->handle(new RequestLoginOtpCommand('nobody@example.com', self::GROUP_ID));
    }

    public function testHandleSendsNothingForAnInvalidEmail(): void
    {
        $this->userQueryRepository->expects(self::never())->method('findByEmailAndGroupId');
        $this->userRepository->expects(self::never())->method('addLoginOtp');
        $this->notifier->expects(self::never())->method('send');

        $this->handler->handle(new RequestLoginOtpCommand('not-an-email', self::GROUP_ID));
    }

    private function credentials(): UserCredentialsView
    {
        return new UserCredentialsView(
            id: self::USER_ID,
            contactInfo: '+40 700 000 000',
            email: 'john.doe@example.com',
            groupId: self::GROUP_ID,
        );
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('ro');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            dirname(__DIR__, 4).'/translations/messages.ro.yaml',
            'ro',
        );

        return $translator;
    }
}
