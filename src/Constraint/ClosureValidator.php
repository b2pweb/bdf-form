<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for @see Closure
 */
class ClosureValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Closure) {
            throw new UnexpectedTypeException($constraint, Closure::class);
        }

        $error = ($constraint->callback)($value, $this->context->getRoot(), $this->context);
        $code = null;

        if ($error === true) {
            return;
        }

        if ($error === false) {
            $error = $constraint->message;
        }

        if ($error) {
            if (is_array($error)) {
                $code = $error['code'] ?? null;
                $error = $error['message'] ?? null;
            }

            $this->context->buildViolation($error ?: $constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode($code ?: 'CUSTOM_ERROR')
                ->addViolation()
            ;
        }
    }
}
