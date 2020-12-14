<?php

namespace Bdf\Form\View;

/**
 * Interface Renderable
 */
interface Renderable
{
    /**
     * Define an attribute value using magic method
     *
     * @param string $name The attribute name
     * @param array $arguments The first argument is the attribute value
     *
     * @return $this
     */
    public function __call(string $name, array $arguments);

    /**
     * Define an attribute value
     *
     * @param string $name The attribute name
     * @param string|bool $value The attribute value. Use true for a simple flag attribute
     *
     * @return $this
     */
    public function set(string $name, $value);

    /**
     * Remove an attribute
     *
     * @param string $name The attribute name
     *
     * @return $this
     */
    public function unset(string $name);

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
