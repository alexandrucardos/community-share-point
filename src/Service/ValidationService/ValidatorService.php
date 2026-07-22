<?php

namespace App\Service\ValidationService;

use App\Domain\ValueObject\ValidatorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ValidatorService implements ValidatorInterface
{
    public function __construct(
        private TranslatorInterface $translator)
    {

    }
    public function validate(
        mixed $value,
        /**
         * $constraints ConstraintDto[]
         */
        array $constraintsDto
    ):void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setTranslator($this->translator)
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
