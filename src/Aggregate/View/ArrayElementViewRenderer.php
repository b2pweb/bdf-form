<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Leaf\View\SimpleFieldHtmlRenderer;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;

/**
 * Default renderer for @see ArrayElementView
 */
final class ArrayElementViewRenderer implements FieldViewRendererInterface
{
    /**
     * @var ArrayElementViewRenderer
     */
    private static $instance;

    /**
     * @var FieldViewRendererInterface
     */
    private $csvRenderer;

    /**
     * ArrayElementViewRenderer constructor.
     *
     * @param FieldViewRendererInterface|null $csvRenderer
     */
    public function __construct(?FieldViewRendererInterface $csvRenderer = null)
    {
        $this->csvRenderer = $csvRenderer ?? SimpleFieldHtmlRenderer::instance();
    }

    /**
     * {@inheritdoc}
     *
     * @param ArrayElementView $view
     */
    public function render(FieldViewInterface $view, array $attributes): string
    {
        if ($view->isCsv()) {
            return $this->csvRenderer->render($view, $attributes);
        }

        // @todo ?
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

        return self::$instance = new self();
    }
}
