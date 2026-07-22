<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\DTO\ConstraintDto;
use App\Domain\ValueObject\Constraint;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EmailValueObjectTest extends TestCase
{
    public function testValidateDelegatesToValidatorWithExpectedConstraints(): void
    {
        $value = 'john.doe@example.com';

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->identicalTo($value),
                $this->callback(function (array $constraints): bool {
                    self::assertCount(4, $constraints);
                    self::assertContainsOnlyInstancesOf(ConstraintDto::class, $constraints);

                    self::assertSame(Constraint::NotNull, $constraints[0]->constraint);
                    self::assertSame([], $constraints[0]->properties);

                    self::assertSame(Constraint::NotBlank, $constraints[1]->constraint);
                    self::assertSame([], $constraints[1]->properties);

                    self::assertSame(Constraint::Email, $constraints[2]->constraint);
                    self::assertSame([], $constraints[2]->properties);

                    self::assertSame(Constraint::Length, $constraints[3]->constraint);
                    self::assertSame(
                        [
                            Constraint::LENGTH_MAX => 100,
                            Constraint::LENGTH_MAX_MSG => 'user.email.max_length'
                        ], $constraints[3]->properties);

                    return true;
                })
            );

        $emailValueObject = new EmailValueObject($validator);

        $emailValueObject($value);

        self::assertSame($value, $emailValueObject->value);
    }
}
