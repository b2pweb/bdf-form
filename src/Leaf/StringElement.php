<?php

namespace Bdf\Form\Leaf;

use Override;
use TypeError;

use function is_object;
use function is_scalar;
use function method_exists;

/**
 * Element for a simple string field
 *
 * @see StringElementBuilder for build the element
 *
 * @extends LeafElement<string>
 */
class StringElement extends LeafElement
{
    #[Override]
    protected function toPhp(mixed $httpValue): ?string
    {
        if (!is_scalar($httpValue)) {
            return null;
        }

        return (string) $httpValue;
    }

    #[Override]
    protected function toHttp(mixed $phpValue): ?string
    {
        return $phpValue;
    }

    #[Override]
    protected function tryCast(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value) && (!is_object($value) || !method_exists($value, '__toString'))) {
            throw new TypeError('The import()\'ed value of a '.static::class.' must be stringable or null');
        }

        return (string) $value;
    }
}
