<?php

namespace Bdf\Form\Leaf\View;

use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use Bdf\Form\View\HtmlRenderer;
use InvalidArgumentException;

/**
 * Renderer for select element
 * Should be used for element with choices
 *
 * @implements FieldViewRendererInterface<FieldViewInterface>
 */
final class SelectHtmlRenderer implements FieldViewRendererInterface
{
    /**
     * @var SelectHtmlRenderer|null
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function render(FieldViewInterface $view, array $attributes): string
    {
        if (!$choices = $view->choices()) {
            throw new InvalidArgumentException('Choices must be provided for render a select element.');
        }

        $attributes['name'] = $view->name();
        $attributes['required'] = $view->required();

        if (!empty($attributes['multiple'])) {
            $attributes['name'] .= '[]';
        }

        $options = '';

        foreach ($choices as $choice) {
            $options .= HtmlRenderer::element('option', ['value' => $choice->value(), 'selected' => $choice->selected()], htmlentities($choice->label()));
        }

        return HtmlRenderer::element('select', $attributes, $options);
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
