<?php

namespace Bdf\Form\Leaf;

/**
 * Element which supports any type of values
 * This element allow to perform any type transformation from transformers on the form declaration
 *
 * Note: it's advisable to declare a custom type instead
 *
 * @template T
 * @extends LeafElement<T>
 */
class AnyElement extends LeafElement
{
    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue)
    {
        return $httpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue)
    {
        return $phpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function sanitize($rawValue)
    {
        return $rawValue;
    }
}
