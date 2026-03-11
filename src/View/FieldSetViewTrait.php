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
    private array $elements = [];

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->elements[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): ElementViewInterface
    {
        return $this->elements[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('FormView is read only');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
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
     * @return array<array-key, string|array>
     */
    public function errors(): array
    {
        $errors = [];

        foreach ($this->elements as $name => $element) {
            if (!$element->hasError()) {
                continue;
            }

            if ($element instanceof FieldSetViewInterface) {
                $errors[$name] = $element->errors();
            } elseif (($error = $element->error()) !== null) {
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
