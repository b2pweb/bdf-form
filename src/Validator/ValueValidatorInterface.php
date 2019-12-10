<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;

/**
 * Validator for an element value
 */
interface ValueValidatorInterface
{
    /**
     * Validate the value
     *
     * @param mixed $value Value to validate
     * @param ElementInterface $element The target element
     *
     * @return FormError The error. Return an empty error if the value is valid
     */
    public function validate($value, ElementInterface $element): FormError;
}
