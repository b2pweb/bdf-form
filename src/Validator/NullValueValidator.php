<?php

namespace Bdf\Form\Validator;

use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;

/**
 * Null object for value validator
 * The value is always considered as valid
 */
final class NullValueValidator implements ValueValidatorInterface
{
    /**
     * @var NullValueValidator
     */
    private static $instance;


    /**
     * {@inheritdoc}
     */
    public function validate($value, ElementInterface $element): FormError
    {
        return FormError::null();
    }

    /**
     * Get a NullValueValidator instance
     *
     * @return static
     */
    public static function instance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self();
    }
}
