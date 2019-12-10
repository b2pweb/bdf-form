<?php

namespace Bdf\Form\Filter;

/**
 * Perform a trim on the input value
 * Supports trim of utf-8 white spaces
 */
final class TrimFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter($value, $input)
    {
        if (!is_string($value)) {
            return $value;
        }

        // unicode trim
        if (null !== $result = @preg_replace('/^[\pZ\p{Cc}]+|[\pZ\p{Cc}]+$/u', '', $value)) {
            return $result;
        }

        return trim($value);
    }
}
