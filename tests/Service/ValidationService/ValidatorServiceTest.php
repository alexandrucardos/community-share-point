<?php

declare(strict_types=1);

namespace App\Tests\Service\ValidationService;

use App\Domain\ValueObject\DTO\ConstraintDto;
use App\Domain\ValueObject\Constraint;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class ValidatorServiceTest extends TestCase
{
    private ValidatorService $validatorService;

    protected function setUp(): void
    {
        $this->validatorService = new ValidatorService(new Translator('ro'));
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
            new ConstraintDto(Constraint::Length,
                [
                    Constraint::LENGTH_MAX => 100,
                ]),
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

    public function testValidateTranslatesConfiguredMessageKeyUsingRealCatalogue(): void
    {
        $validatorService = new ValidatorService($this->createTranslatorWithLoadedCatalogue('ro'));

        try {
            $validatorService->validate('this-value-is-too-long', [
                new ConstraintDto(Constraint::Length, [
                    Constraint::LENGTH_MAX => 1,
                    Constraint::LENGTH_MAX_MSG => 'user.email.max_length',
                ]),
            ]);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame(
                'Lungimea maxima permisa este de 1 de caractere.',
                $exception->getMessage()
            );
        }
    }

    public function testValidateFallsBackToRawKeyWhenNoTranslationIsLoadedForLocale(): void
    {
        $validatorService = new ValidatorService($this->createTranslatorWithLoadedCatalogue('en'));

        try {
            $validatorService->validate('this-value-is-too-long', [
                new ConstraintDto(Constraint::Length, [
                    Constraint::LENGTH_MAX => 1,
                    Constraint::LENGTH_MAX_MSG => 'user.email.max_length',
                ]),
            ]);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('user.email.max_length', $exception->getMessage());
        }
    }

    private function createTranslatorWithLoadedCatalogue(string $locale): Translator
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            dirname(__DIR__, 3).'/translations/messages.ro.yaml',
            'ro',
        );

        return $translator;
    }
}
