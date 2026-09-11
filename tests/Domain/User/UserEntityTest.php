<?php

declare(strict_types=1);

namespace App\Tests\Domain\User;

use App\Domain\User\UserEntity;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class UserEntityTest extends TestCase
{
    private ValidatorInterface $validator;

    private UuidValueObject $id;

    private UserEntity $entity;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->id = new UuidValueObject($this->validator);
        ($this->id)('6f9c2f2e-1c3b-4d5e-8f7a-9b8c7d6e5f4a');
        $this->entity = new UserEntity($this->id);
    }

    public function testGetIdReturnsIdPassedToConstructor(): void
    {
        self::assertSame($this->id, $this->entity->getId());
    }

    public function testContactInfoRoundTripsThroughSetterAndGetter(): void
    {
        $contactInfo = new ContactInfoValueObject($this->validator);
        $contactInfo('phone: +40 722 000 000');

        $this->entity->setContactInfo($contactInfo);

        self::assertSame($contactInfo, $this->entity->getContactInfo());
    }

    public function testGroupIdRoundTripsThroughSetterAndGetter(): void
    {
        $groupId = new UuidValueObject($this->validator);
        $groupId('1f2e3d4c-5b6a-7f8e-9d0c-1b2a3f4e5d6c');

        $this->entity->setGroupId($groupId);

        self::assertSame($groupId, $this->entity->getGroupId());
    }

    public function testEmailRoundTripsThroughSetterAndGetter(): void
    {
        $email = new EmailValueObject($this->validator);
        $email('john.doe@example.com');

        $this->entity->setEmail($email);

        self::assertSame($email, $this->entity->getEmail());
    }

    public function testHashedPasswordRoundTripsThroughSetterAndGetter(): void
    {
        $this->entity->setHashedPassword('hashed-secret');

        self::assertSame('hashed-secret', $this->entity->getHashedPassword());
    }
}
