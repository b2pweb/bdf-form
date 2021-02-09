<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Exception;
use Symfony\Component\Validator\Constraint;

/**
 * Validator for an element value
 *
 * @see ElementInterface::submit()
 *
 * @template T
 */
interface ValueValidatorInterface
{
    /**
     * Validate the value
     *
     * @param T|null $value Value to validate
     * @param ElementInterface $element The target element
     *
     * @return FormError The error. Return an empty error if the value is valid
     */
    public function validate($value, ElementInterface $element): FormError;

    /**
     * Handle a transformer exception
     *
     * @param Exception $exception The exception
     * @param mixed $value The raw value (i.e. not transformed)
     * @param ElementInterface $element The element
     *
     * @return FormError The real error
     */
    public function onTransformerException(Exception $exception, $value, ElementInterface $element): FormError;

    /**
     * Get validator constraints
     *
     * @return Constraint[]
     */
    public function constraints(): array;
}
