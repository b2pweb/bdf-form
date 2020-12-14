<?php

namespace Bdf\Form\Button\View;

use Bdf\Form\View\Renderable;

/**
 * View type for render a button
 */
interface ButtonViewInterface extends Renderable
{
    /**
     * Get the button name (i.e. the http field name)
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the button value
     *
     * @return string
     */
    public function value(): string;

    /**
     * Check if the button is clicked
     *
     * @return bool
     */
    public function clicked(): bool;

    /**
     * Render the button view
     *
     * @param ButtonViewRendererInterface|null $renderer The renderer to use. If null, will use the default renderer (i.e. html renderer)
     *
     * @return string
     */
    public function render(?ButtonViewRendererInterface $renderer = null): string;
}
