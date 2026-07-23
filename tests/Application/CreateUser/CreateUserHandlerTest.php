<?php

declare(strict_types=1);

namespace App\Tests\Application\CreateUser;

use App\Application\CreateUser\CreateUserCommand;
use App\Application\CreateUser\CreateUserHandler;
use App\Domain\User\Exception\EmailAlreadyRegisteredException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\PasswordValueObject;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class CreateUserHandlerTest extends TestCase
{
    private UserRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $userRepository;
    private PasswordHasherInterface&\PHPUnit\Framework\MockObject\Stub $passwordHasher;
    private ValidatorService $validator;
    private CreateUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createStub(PasswordHasherInterface::class);

        $this->validator = new ValidatorService($this->createTranslator());

        $uuid = $this->createStub(UuidInterface::class);
        $uuid->method('generate')->willReturn('11111111-1111-1111-1111-111111111111');

        $this->handler = new CreateUserHandler(
            $this->userRepository,
            $this->passwordHasher,
            new EmailValueObject($this->validator),
            new PasswordValueObject($this->validator),
            new ContactInfoValueObject($this->validator),
            $uuid,
        );
    }

    public function testHandleAddsUserWithHashedPasswordWhenDataIsValid(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hash')->willReturn('hashed-password');

        $this->userRepository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (UserEntity $user): bool {
                self::assertSame('john.doe@example.com', $user->getEmail()->value);
                self::assertSame('+40 700 000 000', $user->getContactInfo());
                self::assertSame('hashed-password', $user->getPassword());
                self::assertNotSame('', $user->getId()->value);

                return true;
            }));

        $this->handler->handle(new CreateUserCommand(
            email: 'john.doe@example.com',
            plainPassword: 'a-strong-password',
            contactInfo: '+40 700 000 000',
        ));
    }

    public function testHandleThrowsWhenEmailIsInvalid(): void
    {
        $this->userRepository->expects($this->never())->method('add');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new CreateUserCommand(
            email: 'not-an-email',
            plainPassword: 'a-strong-password',
            contactInfo: '+40 700 000 000',
        ));
    }

    public function testHandleThrowsWhenPasswordIsTooShort(): void
    {
        $this->userRepository->expects($this->never())->method('add');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new CreateUserCommand(
            email: 'john.doe@example.com',
            plainPassword: 'short',
            contactInfo: '+40 700 000 000',
        ));
    }

    public function testHandleThrowsWhenContactInfoIsBlank(): void
    {
        $this->userRepository->expects($this->never())->method('add');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new CreateUserCommand(
            email: 'john.doe@example.com',
            plainPassword: 'a-strong-password',
            contactInfo: '',
        ));
    }

    public function testHandleThrowsWhenEmailIsAlreadyRegistered(): void
    {
        $existingUser = new UserEntity('existing-id');
        $existingUser->setEmail($this->email('john.doe@example.com'));

        $this->userRepository->method('findByEmail')->willReturn($existingUser);
        $this->userRepository->expects($this->never())->method('add');

        $this->expectException(EmailAlreadyRegisteredException::class);

        $this->handler->handle(new CreateUserCommand(
            email: 'john.doe@example.com',
            plainPassword: 'a-strong-password',
            contactInfo: '+40 700 000 000',
        ));
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
