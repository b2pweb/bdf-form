<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use WeakReference;

/**
 * Validator for @see Closure
 */
class ClosureValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Closure) {
            throw new UnexpectedTypeException($constraint, Closure::class);
        }

        $element = $this->context->getRoot() instanceof WeakReference ? $this->context->getRoot()->get() : null;
        /** @psalm-suppress PossiblyNullArgument */
        $error = ($constraint->callback)($value, $element, $this->context);
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
