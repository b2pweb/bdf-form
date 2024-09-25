<?php

namespace Bdf\Form\Csrf;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @internal
 */
class CsrfConstraintValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CsrfConstraint) {
            throw new UnexpectedTypeException($constraint, CsrfConstraint::class);
        }

        if (!$value instanceof CsrfToken || !$constraint->manager->isTokenValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(CsrfConstraint::INVALID_TOKEN_ERROR)
                ->addViolation()
            ;
        }
    }
}
