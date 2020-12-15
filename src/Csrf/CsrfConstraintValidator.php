<?php

namespace Bdf\Form\Csrf;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @internal
 */
class CsrfConstraintValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param CsrfToken $value
     * @param CsrfConstraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof CsrfToken || !$constraint->manager->isTokenValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(CsrfConstraint::INVALID_TOKEN_ERROR)
                ->addViolation()
            ;
        }
    }
}
