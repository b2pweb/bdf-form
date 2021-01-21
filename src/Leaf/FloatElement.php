<?php

namespace Bdf\Form\Leaf;

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
}
