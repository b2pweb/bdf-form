<?php

namespace Bdf\Form\Leaf;

use TypeError;
use function Webmozart\Assert\Tests\StaticAnalysis\string;

/**
 * Element for a simple string field
 *
 * @see StringElementBuilder for build the element
 *
 * @extends LeafElement<string>
 */
class StringElement extends LeafElement
{
    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue): ?string
    {
        if (!is_scalar($httpValue)) {
            return null;
        }

        return (string) $httpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue): ?string
    {
        return $phpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function tryCast($value): ?string
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
