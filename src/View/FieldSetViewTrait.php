<?php

namespace Bdf\Form\View;

use ArrayIterator;
use BadMethodCallException;
use Iterator;

use function method_exists;

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
    public function offsetGet($offset): ElementViewInterface
    {
        return $this->elements[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('FormView is read only');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
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
     */
    public function errors(): array
    {
        $errors = [];

        foreach ($this->elements as $name => $element) {
            if (!$element->hasError()) {
                continue;
            }

            if ($element instanceof FieldSetViewInterface && method_exists($element, 'errors')) {
                $errors[$name] = $element->errors();
            } elseif ($error = $element->error()) {
                $errors[$name] = $error;
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     *
     * @return Iterator<string, ElementViewInterface>
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->elements);
    }
}
