<?php

declare(strict_types=1);

namespace App\Tests\Application\UpdateUser;

use App\Application\UpdateUser\UpdateUserCommand;
use App\Application\UpdateUser\UpdateUserHandler;
use App\Domain\User\Exception\InvalidCurrentPasswordException;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\PasswordValueObject;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class UpdateUserHandlerTest extends TestCase
{
    private UserRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $userRepository;
    private PasswordHasherInterface&\PHPUnit\Framework\MockObject\Stub $passwordHasher;
    private ValidatorService $validator;
    private UpdateUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $this->validator = new ValidatorService($this->createTranslator());

        $this->handler = new UpdateUserHandler(
            $this->userRepository,
            $this->passwordHasher,
            new PasswordValueObject($this->validator),
        );
    }

    public function testHandleUpdatesContactInfoWhenCurrentPasswordIsCorrect(): void
    {
        $existingUser = $this->buildUser();

        $this->userRepository->method('findByEmail')->with('jane.doe@example.com')->willReturn($existingUser);
        $this->passwordHasher->method('verify')->willReturn(true);

        $this->userRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (UserEntity $user): bool {
                self::assertSame('new contact info', $user->getContactInfo());
                self::assertSame('old-hashed-password', $user->getPassword());

                return true;
            }));

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
        ));
    }

    public function testHandleHashesAndUpdatesPasswordWhenNewPasswordIsProvided(): void
    {
        $existingUser = $this->buildUser();

        $this->userRepository->method('findByEmail')->willReturn($existingUser);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('hash')->willReturn('new-hashed-password');

        $this->userRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (UserEntity $user): bool {
                self::assertSame('new-hashed-password', $user->getPassword());

                return true;
            }));

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
            newPassword: 'a-new-strong-password',
        ));
    }

    public function testHandleThrowsWhenUserDoesNotExist(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->userRepository->expects($this->never())->method('update');

        $this->expectException(UserNotFoundException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'missing@example.com',
            currentPassword: 'whatever',
            contactInfo: 'new contact info',
        ));
    }

    public function testHandleThrowsWhenCurrentPasswordIsIncorrect(): void
    {
        $this->userRepository->method('findByEmail')->willReturn($this->buildUser());
        $this->passwordHasher->method('verify')->willReturn(false);
        $this->userRepository->expects($this->never())->method('update');

        $this->expectException(InvalidCurrentPasswordException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'wrong-password',
            contactInfo: 'new contact info',
        ));
    }

    public function testHandleThrowsWhenNewPasswordIsTooShort(): void
    {
        $this->userRepository->method('findByEmail')->willReturn($this->buildUser());
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->userRepository->expects($this->never())->method('update');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
            newPassword: 'short',
        ));
    }

    private function buildUser(): UserEntity
    {
        $user = new UserEntity('user-id');
        $user->setEmail($this->email('jane.doe@example.com'));
        $user->setPassword('old-hashed-password');
        $user->setContactInfo('old contact info');
        $user->setGroupId('');

        return $user;
    }

    private function email(string $value): EmailValueObject
    {
        return (new EmailValueObject($this->validator))($value);
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('ro');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            dirname(__DIR__, 3).'/translations/messages.ro.yaml',
            'ro',
        );

        return $translator;
    }
}
