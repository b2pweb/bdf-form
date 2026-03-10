<?php

namespace Bdf\Form\Leaf;

use Override;
use TypeError;

/**
 * Element for an integer
 *
 * @see IntegerElementBuilder for build the element
 *
 * @extends LeafElement<int>
 */
class IntegerElement extends LeafElement
{
    #[Override]
    protected function toPhp($httpValue): ?int
    {
        return $httpValue === null || $httpValue === '' ? null : (int) $httpValue;
    }

    #[Override]
    protected function toHttp($phpValue): ?string
    {
        return $phpValue === null ? null : (string) $phpValue;
    }

    #[Override]
    protected function tryCast($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new TypeError('The import()\'ed value of a '.static::class.' must be numeric or null');
        }

        return (int) $value;
    }
}
