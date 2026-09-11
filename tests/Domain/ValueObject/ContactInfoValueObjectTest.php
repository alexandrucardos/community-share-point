<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Constraint;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\DTO\ConstraintDto;
use App\Domain\ValueObject\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class ContactInfoValueObjectTest extends TestCase
{
    public function testValidateDelegatesToValidatorWithExpectedConstraints(): void
    {
        $value = 'phone: +40 722 000 000';

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($value),
                $this->callback(function (array $constraints): bool {
                    self::assertCount(2, $constraints);
                    self::assertContainsOnlyInstancesOf(ConstraintDto::class, $constraints);

                    self::assertSame(Constraint::NotBlank, $constraints[0]->constraint);
                    self::assertSame(
                        [Constraint::NOT_BLANK_MSG => 'user.contact_info.not_blank'],
                        $constraints[0]->properties
                    );

                    self::assertSame(Constraint::Length, $constraints[1]->constraint);
                    self::assertSame(
                        [
                            Constraint::LENGTH_MAX => 100,
                            Constraint::LENGTH_MAX_MSG => 'user.contact_info.max_length'
                        ],
                        $constraints[1]->properties
                    );

                    return true;
                })
            );

        $contactInfoValueObject = new ContactInfoValueObject($validator);

        $result = $contactInfoValueObject($value);

        self::assertSame($result, $contactInfoValueObject);
        self::assertSame($value, $contactInfoValueObject->value);
    }

    public function testPropagatesValidationExceptionFromValidator(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')
            ->willThrowException(new \InvalidArgumentException('user.contact_info.not_blank'));

        $contactInfoValueObject = new ContactInfoValueObject($validator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('user.contact_info.not_blank');

        $contactInfoValueObject('');
    }

    public function testSerializeRoundTripPreservesValue(): void
    {
        $value = 'phone: +40 722 000 000';

        $validator = $this->createMock(ValidatorInterface::class);

        $contactInfoValueObject = new ContactInfoValueObject($validator);
        $contactInfoValueObject($value);

        $serialized = serialize($contactInfoValueObject);
        $unserialized = unserialize($serialized);

        self::assertInstanceOf(ContactInfoValueObject::class, $unserialized);
        self::assertSame($value, $unserialized->value);
    }
}
