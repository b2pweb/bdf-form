<?php

namespace Bdf\Form\Button\View;

use Bdf\Form\View\HtmlRenderer;

/**
 * Renderer for @see ButtonViewInterface
 */
final class ButtonViewRenderer implements ButtonViewRendererInterface
{
    /**
     * @var self|null
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function render(ButtonViewInterface $view, array $attributes): string
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'submit';
        }

        $attributes['name'] = $view->name();
        $attributes['value'] = $view->value();

        $inner = $attributes['inner'] ?? null;
        unset($attributes['inner']);

        return HtmlRenderer::element($inner !== null ? 'button' : 'input', $attributes, $inner);
    }

    /**
     * Get the renderer instance
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self;
    }
}
