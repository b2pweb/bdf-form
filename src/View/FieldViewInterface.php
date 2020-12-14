<?php

namespace Bdf\Form\View;

/**
 * Base type for HTTP input / field
 * The implementations must be renderable
 */
interface FieldViewInterface extends ElementViewInterface, Renderable
{
    /**
     * The HTTP field name
     *
     * @return string
     */
    public function name(): string;

    /**
     * The HTTP value
     *
     * @return mixed
     */
    public function value();

    /**
     * Does the current field is required (i.e. the value must not be empty)
     *
     * @return bool
     */
    public function required(): bool;

    /**
     * An array of constraints
     * The return value consists of a map with the constraint class name as key, and attributes as value
     * Like:
     * [
     *     Length::class => ['min' => 5, 'max' => 30],
     *     NotBlank::class => [],
     * ]
     *
     * @return array
     *
     * @see ConstraintsNormalizer::normalize() For normalize symfony constraints
     */
    public function constraints(): array;

    /**
     * Render the field view
     *
     * @param FieldViewRendererInterface|null $renderer The renderer to use. If null, will use the default renderer (i.e. html renderer)
     *
     * @return string
     */
    public function render(?FieldViewRendererInterface $renderer = null): string;
}
