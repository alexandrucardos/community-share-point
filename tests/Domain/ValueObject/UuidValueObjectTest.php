<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Constraint;
use App\Domain\ValueObject\DTO\ConstraintDto;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class UuidValueObjectTest extends TestCase
{
    public function testValidateDelegatesToValidatorWithUuidConstraint(): void
    {
        $value = '0d3f4a60-8f0e-4b3b-9d1f-2f1c0b0a0908';

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($value),
                $this->callback(function (array $constraints): bool {
                    self::assertCount(1, $constraints);
                    self::assertContainsOnlyInstancesOf(ConstraintDto::class, $constraints);

                    self::assertSame(Constraint::Uuid, $constraints[0]->constraint);
                    self::assertSame([], $constraints[0]->properties);

                    return true;
                })
            );

        $uuidValueObject = new UuidValueObject($validator);

        $result = $uuidValueObject($value);

        self::assertSame($result, $uuidValueObject);
        self::assertSame($value, $uuidValueObject->value);
    }

    public function testPropagatesValidationExceptionFromValidator(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')
            ->willThrowException(new \InvalidArgumentException('invalid uuid'));

        $uuidValueObject = new UuidValueObject($validator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid uuid');

        $uuidValueObject('not-a-uuid');
    }

    public function testSerializeRoundTripPreservesValue(): void
    {
        $value = '9f8e7d6c-5b4a-3f2e-1d0c-9b8a7f6e5d4c';

        $validator = $this->createMock(ValidatorInterface::class);

        $uuidValueObject = new UuidValueObject($validator);
        $uuidValueObject($value);

        $serialized = serialize($uuidValueObject);
        $unserialized = unserialize($serialized);

        self::assertInstanceOf(UuidValueObject::class, $unserialized);
        self::assertSame($value, $unserialized->value);
    }
}
