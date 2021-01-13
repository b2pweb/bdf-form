<?php

namespace Bdf\Form\View;

/**
 * Base type for perform the render of a field view
 *
 * @template T as FieldViewInterface
 */
interface FieldViewRendererInterface
{
    /**
     * Render the field
     *
     * @param T $view Field to render
     * @param array $attributes Custom attributes
     *
     * @return string
     */
    public function render(FieldViewInterface $view, array $attributes): string;
}
