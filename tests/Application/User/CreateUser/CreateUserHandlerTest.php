<?php

declare(strict_types=1);

namespace App\Tests\Application\User\CreateUser;

use App\Application\User\CreateUser\CreateUserCommand;
use App\Application\User\CreateUser\CreateUserHandler;
use App\Domain\User\Exception\EmailAndGroupAlreadyRegisteredException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class CreateUserHandlerTest extends TestCase
{
    private const GROUP_ID = '11111111-1111-4111-8111-111111111111';

    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordHasherInterface&Stub $passwordHasher;
    private ValidatorService $validator;
    private CreateUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createStub(PasswordHasherInterface::class);

        $this->validator = new ValidatorService($this->createTranslator());

        $uuid = $this->createStub(UuidInterface::class);
        $uuid->method('generate')->willReturn('22222222-2222-4222-8222-222222222222');

        $this->handler = new CreateUserHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->validator,
            $uuid,
        );
    }

    public function testHandleAddsUserWithHashedPasswordWhenDataIsValid(): void
    {
        $this->userRepository->method('findByEmailAndGroupId')->willReturn(null);
        $this->passwordHasher->method('hash')->willReturn('hashed-password');

        $this->userRepository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (UserEntity $user): bool {
                self::assertSame('john.doe@example.com', $user->getEmail()->value);
                self::assertSame('+40 700 000 000', $user->getContactInfo()->value);
                self::assertSame('hashed-password', $user->getHashedPassword());
                self::assertSame('22222222-2222-4222-8222-222222222222', $user->getId()->value);
                self::assertSame(self::GROUP_ID, $user->getGroupId()->value);

                return true;
            }));

        $this->handler->handle(new CreateUserCommand(
            email: 'john.doe@example.com',
            plainPassword: 'a-strong-password',
            contactInfo: '+40 700 000 000',
            groupId: self::GROUP_ID,
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
            groupId: self::GROUP_ID,
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
            groupId: self::GROUP_ID,
        ));
    }

    public function testHandleThrowsWhenGroupIdIsNotAUuid(): void
    {
        $this->userRepository->expects($this->never())->method('add');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new CreateUserCommand(
            email: 'john.doe@example.com',
            plainPassword: 'a-strong-password',
            contactInfo: '+40 700 000 000',
            groupId: 'not-a-uuid',
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
            groupId: self::GROUP_ID,
        ));
    }

    public function testHandleThrowsWhenEmailIsAlreadyRegisteredInTheGroup(): void
    {
        $existingUser = new UserEntity($this->uuid('33333333-3333-4333-8333-333333333333'));
        $existingUser->setEmail($this->email('john.doe@example.com'));

        $this->userRepository->method('findByEmailAndGroupId')->willReturn($existingUser);
        $this->userRepository->expects($this->never())->method('add');

        $this->expectException(EmailAndGroupAlreadyRegisteredException::class);

        $this->handler->handle(new CreateUserCommand(
            email: 'john.doe@example.com',
            plainPassword: 'a-strong-password',
            contactInfo: '+40 700 000 000',
            groupId: self::GROUP_ID,
        ));
    }

    private function email(string $value): EmailValueObject
    {
        return (new EmailValueObject($this->validator))($value);
    }

    private function uuid(string $value): UuidValueObject
    {
        return (new UuidValueObject($this->validator))($value);
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
