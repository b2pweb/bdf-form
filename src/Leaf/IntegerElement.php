<?php

namespace Bdf\Form\Leaf;

/**
 * Element for an integer
 */
final class IntegerElement extends LeafElement
{
    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue): ?int
    {
        return $httpValue === null || $httpValue === '' ? null : (int) $httpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue): ?string
    {
        return $phpValue === null ? null : (string) $phpValue;
    }
}
