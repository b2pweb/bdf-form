<?php

namespace Bdf\Form\Leaf;

use TypeError;

/**
 * Element for a float value
 *
 * @see FloatElementBuilder for build the element
 *
 * @extends LeafElement<float>
 */
class FloatElement extends LeafElement
{
    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue): ?float
    {
        return $httpValue === null || $httpValue === '' ? null : (float) $httpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue): ?string
    {
        return $phpValue === null ? null : (string) $phpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function tryCast($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new TypeError('The import()\'ed value of a '.static::class.' must be numeric or null');
        }

        return (float) $value;
    }
}
