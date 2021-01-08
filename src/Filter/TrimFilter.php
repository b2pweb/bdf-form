<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildInterface;

/**
 * Perform a trim on the input value
 * Supports trim of utf-8 white spaces
 *
 * @see ChildBuilder::trim() For enable trim filter
 */
final class TrimFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter($value, ChildInterface $input)
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
