<?php

namespace Bdf\Form\Leaf;

use Override;
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
    #[Override]
    protected function toPhp($httpValue): ?float
    {
        return $httpValue === null || $httpValue === '' ? null : (float) $httpValue;
    }

    #[Override]
    protected function toHttp($phpValue): ?string
    {
        return $phpValue === null ? null : (string) $phpValue;
    }

    #[Override]
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
