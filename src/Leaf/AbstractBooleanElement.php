<?php

namespace Bdf\Form\Leaf;

use Override;
use TypeError;

use function is_scalar;

/**
 * Base element for boolean value
 *
 * @see BooleanElementBuilder for build the element
 *
 * @method bool|null value()
 * @extends LeafElement<bool>
 */
abstract class AbstractBooleanElement extends LeafElement
{
    #[Override]
    final protected function tryCast(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (!is_scalar($value)) {
            throw new TypeError('The import()\'ed value of a '.static::class.' must be a scalar value or null');
        }

        return (bool) $value;
    }
}
