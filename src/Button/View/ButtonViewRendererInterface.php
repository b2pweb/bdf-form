<?php

namespace Bdf\Form\Button\View;

/**
 * Renderer for @see ButtonViewInterface
 */
interface ButtonViewRendererInterface
{
    /**
     * Render the button view
     *
     * @param ButtonViewInterface $view View to render
     * @param array $attributes The attributes
     *
     * @return string
     */
    public function render(ButtonViewInterface $view, array $attributes): string;
}
