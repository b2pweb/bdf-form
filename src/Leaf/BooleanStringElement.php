<?php

namespace Bdf\Form\Leaf;

use function filter_var;
use function is_bool;

/**
 * Element for boolean value using string representation
 *
 * A boolean string is considered as true when the value is on of the following: "true", "on", "yes", "1"
 * The value is considered as false when the value is on of the following: "false", "off", "no", "0"
 * For any other value, the value is considered as null
 *
 * Note: Case and space are ignored, so "True", "ON", " yes " are valid values
 *
 * @see BooleanElementBuilder::booleanString() For build the element
 */
class BooleanStringElement extends AbstractBooleanElement
{
    /**
     * {@inheritdoc}
     *
     * @return scalar|null
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    protected function sanitize($rawValue)
    {
        // Does not cast to string, to allow boolean value
        return is_scalar($rawValue) ? $rawValue : null;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|null
     */
    protected function toPhp($httpValue): ?bool
    {
        if ($httpValue === null || $httpValue === '') {
            return null;
        }

        if (is_bool($httpValue)) {
            return $httpValue;
        }

        /** @var bool|null */
        return filter_var((string) $httpValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue): ?string
    {
        if ($phpValue === null) {
            return null;
        }

        return $phpValue ? 'true' : 'false';
    }
}
