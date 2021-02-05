<?php

namespace Bdf\Form\View;

use ArrayIterator;
use BadMethodCallException;

/**
 * Implements @see FieldSetViewInterface
 *
 * @psalm-require-implements FieldSetViewInterface
 */
trait FieldSetViewTrait
{
    /**
     * @var array<string, ElementViewInterface>
     */
    private $elements = [];

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->elements[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->elements[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('FormView is read only');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('FormView is read only');
    }

    /**
     * {@inheritdoc}
     */
    public function hasError(): bool
    {
        if ($this->error() !== null) {
            return true;
        }

        foreach ($this->elements as $element) {
            if ($element->hasError()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Iterator<string, ElementViewInterface>
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }
}
