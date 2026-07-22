<?php

declare(strict_types=1);

namespace App\Tests\Service\ValidationService;

use App\Domain\DTO\ConstraintDto;
use App\Domain\ValueObject\Constraint;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\TestCase;

final class ValidatorServiceTest extends TestCase
{
    private ValidatorService $validatorService;

    protected function setUp(): void
    {
        $this->validatorService = new ValidatorService();
    }

    public function testValidateDoesNotThrowWhenNoConstraintsGiven(): void
    {
        $this->validatorService->validate('anything', []);

        $this->addToAssertionCount(1);
    }

    public function testValidateDoesNotThrowWhenValueSatisfiesAllConstraints(): void
    {
        $this->validatorService->validate('john.doe@example.com', [
            new ConstraintDto(Constraint::NotNull),
            new ConstraintDto(Constraint::NotBlank),
            new ConstraintDto(Constraint::Email),
            new ConstraintDto(Constraint::Length, [Constraint::LENGTH_MAX => 100]),
        ]);

        $this->addToAssertionCount(1);
    }

    public function testValidateThrowsWhenValueIsNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validatorService->validate(null, [
            new ConstraintDto(Constraint::NotNull),
        ]);
    }

    public function testValidateThrowsWhenValueIsBlank(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validatorService->validate('', [
            new ConstraintDto(Constraint::NotBlank),
        ]);
    }

    public function testValidateThrowsWhenValueIsNotAValidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validatorService->validate('not-an-email', [
            new ConstraintDto(Constraint::Email),
        ]);
    }

    public function testValidateThrowsWhenValueExceedsMaxLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validatorService->validate('this-value-is-too-long', [
            new ConstraintDto(Constraint::Length, [Constraint::LENGTH_MAX => 5]),
        ]);
    }

    public function testValidateThrowsWhenValueIsBelowMinLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validatorService->validate('ab', [
            new ConstraintDto(Constraint::Length, [Constraint::LENGTH_MIN => 5]),
        ]);
    }

    public function testValidateThrowsWithCombinedMessagesWhenMultipleConstraintsFail(): void
    {
        try {
            $this->validatorService->validate('', [
                new ConstraintDto(Constraint::NotBlank),
                new ConstraintDto(Constraint::Email),
            ]);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $exception) {
            self::assertNotSame('', $exception->getMessage());
        }
    }
}
