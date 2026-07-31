<?php

namespace App\Service\ValidationService;

use App\Domain\ValueObject\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Domain\ValueObject\DTO\ConstraintDto;

final class ValidatorService implements ValidatorInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {

    }
    /**
     * @param $constraintsDto ConstraintDto[]
     */
    public function validate(
        mixed $value,
        array $constraintsDto
    ):void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setTranslator($this->translator)
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                Email::class.'Validator' => new EmailValidator(Email::VALIDATION_MODE_HTML5),
            ]))
            ->getValidator();

        $frameworkConstraints =[];

        foreach ($constraintsDto as $constraint) {
            $frameworkConstraints[] = ConstraintMapper::fetchAssert(
                constraint: $constraint->constraint,
                properties: $constraint->properties,
            );
        }

        $violations = $validator->validate($value, $frameworkConstraints);

        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getMessage();
            }
            throw new \InvalidArgumentException(implode(' ', $messages));
        }
    }
}
