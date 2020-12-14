<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use Bdf\Form\View\HtmlRenderer;

/**
 * Default renderer for @see BooleanElementView
 */
final class CheckboxHtmlRenderer implements FieldViewRendererInterface
{
    /**
     * @var CheckboxHtmlRenderer
     */
    private static $instance;

    /**
     * {@inheritdoc}
     *
     * @param BooleanElementView $view
     */
    public function render(FieldViewInterface $view, array $attributes): string
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'checkbox';
        }

        $attributes['name'] = $view->name();
        $attributes['value'] = $view->httpValue();
        $attributes['checked'] = $view->checked();

        return HtmlRenderer::element('input', $attributes);
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
