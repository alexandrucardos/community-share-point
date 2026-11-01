<?php

declare(strict_types=1);

namespace App\Tests\Application\User\VerifyLoginOtp;

use App\Domain\User\LoginOtpEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Application\User\VerifyLoginOtp\InvalidLoginOtpException;
use App\Application\User\VerifyLoginOtp\VerifyLoginOtpCommand;
use App\Application\User\VerifyLoginOtp\VerifyLoginOtpHandler;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class VerifyLoginOtpHandlerTest extends TestCase
{
    private const GROUP_ID = '11111111-1111-4111-8111-111111111111';
    private const OTP_ID = '33333333-3333-4333-8333-333333333333';

    private UserRepositoryInterface&MockObject $userRepository;
    private VerifyLoginOtpHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new VerifyLoginOtpHandler(
            $this->userRepository,
            new ValidatorService($this->createTranslator()),
        );
    }

    public function testHandleMarksTheCodeUsedWhenItMatches(): void
    {
        $this->userRepository->method('findLatestValidLoginOtp')->willReturn($this->validCode('123456'));

        $this->userRepository->expects(self::once())
            ->method('updateLoginOtp')
            ->with(self::callback(
                static fn (LoginOtpEntity $otp): bool => $otp->isUsed(),
            ));

        $this->handler->handle($this->command('123456'));
    }

    public function testHandleRejectsAWrongCodeWithoutConsumingIt(): void
    {
        $this->userRepository->method('findLatestValidLoginOtp')->willReturn($this->validCode('123456'));

        $this->userRepository->expects(self::never())->method('updateLoginOtp');

        $this->expectException(InvalidLoginOtpException::class);

        $this->handler->handle($this->command('000000'));
    }

    public function testHandleRejectsWhenNoValidCodeExists(): void
    {
        $this->userRepository->method('findLatestValidLoginOtp')->willReturn(null);

        $this->userRepository->expects(self::never())->method('updateLoginOtp');

        $this->expectException(InvalidLoginOtpException::class);

        $this->handler->handle($this->command('123456'));
    }

    public function testHandleRejectsAMalformedEmailWithoutQuerying(): void
    {
        $this->userRepository->expects(self::never())->method('findLatestValidLoginOtp');

        $this->expectException(InvalidLoginOtpException::class);

        $this->handler->handle(new VerifyLoginOtpCommand('not-an-email', self::GROUP_ID, '123456'));
    }

    private function command(string $code): VerifyLoginOtpCommand
    {
        return new VerifyLoginOtpCommand('john.doe@example.com', self::GROUP_ID, $code);
    }

    private function validCode(string $code): LoginOtpEntity
    {
        $validator = $this->createMock(ValidatorInterface::class);

        return LoginOtpEntity::issue(
            (new UuidValueObject($validator))(self::OTP_ID),
            (new EmailValueObject($validator))('john.doe@example.com'),
            (new UuidValueObject($validator))(self::GROUP_ID),
            $code,
            new \DateTimeImmutable(),
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
