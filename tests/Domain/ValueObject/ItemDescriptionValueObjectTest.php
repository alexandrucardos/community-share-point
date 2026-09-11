<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Constraint;
use App\Domain\ValueObject\DTO\ConstraintDto;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class ItemDescriptionValueObjectTest extends TestCase
{
    public function testValidateDelegatesToValidatorWithExpectedConstraints(): void
    {
        $value = 'Cordless drill with two batteries';

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
                        [Constraint::NOT_BLANK_MSG => 'item.description.not_blank'],
                        $constraints[0]->properties
                    );

                    self::assertSame(Constraint::Length, $constraints[1]->constraint);
                    self::assertSame(
                        [
                            Constraint::LENGTH_MAX => 500,
                            Constraint::LENGTH_MAX_MSG => 'item.description.max_length'
                        ],
                        $constraints[1]->properties
                    );

                    return true;
                })
            );

        $itemDescriptionValueObject = new ItemDescriptionValueObject($validator);

        $itemDescriptionValueObject($value);

        self::assertSame($value, $itemDescriptionValueObject->value);
    }

    public function testPropagatesValidationExceptionFromValidator(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')
            ->willThrowException(new \InvalidArgumentException('item.description.max_length'));

        $itemDescriptionValueObject = new ItemDescriptionValueObject($validator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item.description.max_length');

        $itemDescriptionValueObject(str_repeat('x', 501));
    }
}
