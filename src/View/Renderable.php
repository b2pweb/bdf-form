<?php

namespace Bdf\Form\View;

use phpDocumentor\Reflection\Types\Scalar;
use Stringable;

/**
 * Base type for a renderable element of the form view tree
 */
interface Renderable extends Stringable
{
    /**
     * Define an attribute value using magic method
     *
     * @param string $name The attribute name
     * @param array $arguments The first argument is the attribute value
     *
     * @return $this
     */
    public function __call(string $name, array $arguments): static;

    /**
     * Define an attribute value
     *
     * @param string $name The attribute name
     * @param scalar|bool $value The attribute value. Use true for a simple flag attribute
     *
     * @return $this
     */
    public function set(string $name, string|int|float|bool $value): static;

    /**
     * Define multiple attributes
     * Use key-value for simple attributes, and only value for flag attributes
     *
     * Example:
     * <code>
     *     $element->with(['id' => 'my-id', 'required']);
     * </code>
     *
     * @param array<scalar> $attributes
     * @return $this
     * @since 1.5
     */
    public function with(array $attributes): static;

    /**
     * Remove an attribute
     *
     * @param string $name The attribute name
     *
     * @return $this
     */
    public function unset(string $name): static;

    /**
     * Get all defined attributes
     *
     * @return array
     */
    public function attributes(): array;

    /**
     * Render the element view
     *
     * @return string
     */
    public function render(): string;

    /**
     * Render the field
     *
     * @return string
     */
    public function __toString(): string;
}
