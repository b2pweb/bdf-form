<?php

namespace Bdf\Form\Leaf;

use Override;

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
    #[Override]
    protected function toPhp(mixed $httpValue): mixed
    {
        return $httpValue;
    }

    #[Override]
    protected function toHttp(mixed $phpValue): mixed
    {
        return $phpValue;
    }

    #[Override]
    protected function sanitize(mixed $rawValue): mixed
    {
        return $rawValue;
    }
}
