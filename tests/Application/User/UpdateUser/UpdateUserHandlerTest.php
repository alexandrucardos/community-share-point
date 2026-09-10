<?php

declare(strict_types=1);

namespace App\Tests\Application\User\UpdateUser;

use App\Application\User\UpdateUser\UpdateUserCommand;
use App\Application\User\UpdateUser\UpdateUserHandler;
use App\Domain\User\Exception\InvalidCurrentPasswordException;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\PasswordValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class UpdateUserHandlerTest extends TestCase
{
    private const GROUP_ID = '11111111-1111-4111-8111-111111111111';
    private const USER_ID = '22222222-2222-4222-8222-222222222222';

    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordHasherInterface&Stub $passwordHasher;
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
            $this->validator,
        );
    }

    public function testHandleUpdatesContactInfoWhenCurrentPasswordIsCorrect(): void
    {
        $existingUser = $this->buildUser();

        $this->userRepository->method('findByEmailAndGroupId')->willReturn($existingUser);
        $this->passwordHasher->method('verify')->willReturn(true);

        $this->userRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (UserEntity $user): bool {
                self::assertSame('new contact info', $user->getContactInfo()->value);
                self::assertSame('old-hashed-password', $user->getHashedPassword());

                return true;
            }));

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
            groupId: self::GROUP_ID,
        ));
    }

    public function testHandleHashesAndUpdatesPasswordWhenNewPasswordIsProvided(): void
    {
        $existingUser = $this->buildUser();

        $this->userRepository->method('findByEmailAndGroupId')->willReturn($existingUser);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('hash')->willReturn('new-hashed-password');

        $this->userRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (UserEntity $user): bool {
                self::assertSame('new-hashed-password', $user->getHashedPassword());

                return true;
            }));

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
            groupId: self::GROUP_ID,
            newPassword: 'a-new-strong-password',
        ));
    }

    public function testHandleThrowsWhenUserDoesNotExist(): void
    {
        $this->userRepository->method('findByEmailAndGroupId')->willReturn(null);
        $this->userRepository->expects($this->never())->method('update');

        $this->expectException(UserNotFoundException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'missing@example.com',
            currentPassword: 'whatever',
            contactInfo: 'new contact info',
            groupId: self::GROUP_ID,
        ));
    }

    public function testHandleThrowsWhenCurrentPasswordIsIncorrect(): void
    {
        $this->userRepository->method('findByEmailAndGroupId')->willReturn($this->buildUser());
        $this->passwordHasher->method('verify')->willReturn(false);
        $this->userRepository->expects($this->never())->method('update');

        $this->expectException(InvalidCurrentPasswordException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'wrong-password',
            contactInfo: 'new contact info',
            groupId: self::GROUP_ID,
        ));
    }

    public function testHandleThrowsWhenNewPasswordIsTooShort(): void
    {
        $this->userRepository->method('findByEmailAndGroupId')->willReturn($this->buildUser());
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->userRepository->expects($this->never())->method('update');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
            groupId: self::GROUP_ID,
            newPassword: 'short',
        ));
    }

    public function testHandleThrowsWhenContactInfoIsBlank(): void
    {
        $this->userRepository->method('findByEmailAndGroupId')->willReturn($this->buildUser());
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->userRepository->expects($this->never())->method('update');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: '',
            groupId: self::GROUP_ID,
        ));
    }

    public function testHandleThrowsWhenEmailIsInvalid(): void
    {
        $this->userRepository->expects($this->never())->method('findByEmailAndGroupId');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'not-an-email',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
            groupId: self::GROUP_ID,
        ));
    }

    public function testHandleThrowsWhenGroupIdIsNotAUuid(): void
    {
        $this->userRepository->expects($this->never())->method('findByEmailAndGroupId');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new UpdateUserCommand(
            email: 'jane.doe@example.com',
            currentPassword: 'correct-password',
            contactInfo: 'new contact info',
            groupId: 'not-a-uuid',
        ));
    }

    private function buildUser(): UserEntity
    {
        $user = new UserEntity($this->uuid(self::USER_ID));
        $user->setEmail($this->email('jane.doe@example.com'));
        $user->setHashedPassword('old-hashed-password');
        $user->setContactInfo((new ContactInfoValueObject($this->validator))('old contact info'));
        $user->setGroupId($this->uuid(self::GROUP_ID));

        return $user;
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
