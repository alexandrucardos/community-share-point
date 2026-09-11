<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Constraint;
use App\Domain\ValueObject\DTO\ConstraintDto;
use App\Domain\ValueObject\ItemNameValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class ItemNameValueObjectTest extends TestCase
{
    public function testValidateDelegatesToValidatorWithExpectedConstraints(): void
    {
        $value = 'Drill';

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
                        [Constraint::NOT_BLANK_MSG => 'item.name.not_blank'],
                        $constraints[0]->properties
                    );

                    self::assertSame(Constraint::Length, $constraints[1]->constraint);
                    self::assertSame(
                        [
                            Constraint::LENGTH_MAX => 100,
                            Constraint::LENGTH_MAX_MSG => 'item.name.max_length'
                        ],
                        $constraints[1]->properties
                    );

                    return true;
                })
            );

        $itemNameValueObject = new ItemNameValueObject($validator);

        $itemNameValueObject($value);

        self::assertSame($value, $itemNameValueObject->value);
    }

    public function testPropagatesValidationExceptionFromValidator(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')
            ->willThrowException(new \InvalidArgumentException('item.name.not_blank'));

        $itemNameValueObject = new ItemNameValueObject($validator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item.name.not_blank');

        $itemNameValueObject('');
    }
}
