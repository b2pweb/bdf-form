<?php

namespace Bdf\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @internal
 */
final class TransformerExceptionConstraintValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof TransformerExceptionConstraint) {
            throw new UnexpectedTypeException($constraint, TransformerExceptionConstraint::class);
        }

        if ($constraint->validationCallback) {
            if (!($constraint->validationCallback)($value, $constraint, $this->context->getRoot())) {
                return;
            }
        }

        $this->context->buildViolation($constraint->message ?: $constraint->exception->getMessage())
            ->setCode($constraint->code)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->addViolation()
        ;
    }
}
