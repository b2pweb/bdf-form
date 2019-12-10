<?php

namespace Bdf\Form\Leaf;

/**
 * Element for a simple string field
 */
final class StringElement extends LeafElement
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
}
