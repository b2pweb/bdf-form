<?php

namespace Bdf\Form\View;

/**
 * Base type for perform the render of a field view
 */
interface FieldViewRendererInterface
{
    /**
     * Render the field
     *
     * @param FieldViewInterface $view Field to render
     * @param array $attributes Custom attributes
     *
     * @return string
     */
    public function render(FieldViewInterface $view, array $attributes): string;
}
