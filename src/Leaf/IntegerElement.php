<?php

namespace Bdf\Form\Leaf;

use Override;
use TypeError;

use function is_numeric;

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
    protected function toPhp(mixed $httpValue): ?int
    {
        return $httpValue === null || $httpValue === '' ? null : (int) $httpValue;
    }

    #[Override]
    protected function toHttp(mixed $phpValue): ?string
    {
        return $phpValue === null ? null : (string) $phpValue;
    }

    #[Override]
    protected function tryCast(mixed $value): ?int
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
