<?php

namespace Bdf\Form\Validator;

use LogicException;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @internal
 */
final class TransformerExceptionConstraintValidator extends ConstraintValidator
{
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TransformerExceptionConstraint) {
            throw new UnexpectedTypeException($constraint, TransformerExceptionConstraint::class);
        }

        if ($constraint->validationCallback !== null) {
            if (!($constraint->validationCallback)($value, $constraint, $this->context->getRoot())) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message ?? $constraint->exception?->getMessage() ?? throw new LogicException('TransformerExceptionConstraint must have a message or an exception'))
            ->setCode($constraint->code)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->addViolation()
        ;
    }
}
