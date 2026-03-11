<?php

namespace Bdf\Form\View;

use ArgumentCountError;
use TypeError;

use function is_int;
use function is_scalar;

/**
 * Implements @see Renderable
 *
 * @psalm-require-implements Renderable
 */
trait RenderableTrait
{
    /**
     * @var array<string, scalar>
     */
    private array $attributes = [];

    /**
     * {@inheritdoc}
     */
    public function __call(string $name, array $arguments): static
    {
        if (empty($arguments)) {
            throw new ArgumentCountError('Missing the attribute value.');
        }

        $this->set($name, $arguments[0]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, int|string|float|bool $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function with(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            if (!is_scalar($value)) {
                throw new TypeError('The attribute value must be a scalar value.');
            }

            if (is_int($name)) {
                $this->attributes[(string) $value] = true;
            } else {
                $this->attributes[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unset(string $name): static
    {
        unset($this->attributes[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function render(): string;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
