<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Leaf\View\SelectHtmlRenderer;
use Bdf\Form\Leaf\View\SimpleFieldHtmlRenderer;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;

/**
 * Default renderer for @see ArrayElementView
 *
 * @implements FieldViewRendererInterface<ArrayElementView>
 */
final class ArrayElementViewRenderer implements FieldViewRendererInterface
{
    /**
     * @var ArrayElementViewRenderer|null
     */
    private static $instance;

    /**
     * @var FieldViewRendererInterface
     */
    private $csvRenderer;

    /**
     * @var FieldViewRendererInterface
     */
    private $selectRenderer;

    /**
     * ArrayElementViewRenderer constructor.
     *
     * @param FieldViewRendererInterface|null $csvRenderer Renderer used for simple CSV element
     * @param FieldViewRendererInterface|null $selectRenderer Renderer for a select multiple (only if choices are defined)
     */
    public function __construct(?FieldViewRendererInterface $csvRenderer = null, ?FieldViewRendererInterface $selectRenderer = null)
    {
        $this->csvRenderer = $csvRenderer ?? SimpleFieldHtmlRenderer::instance();
        $this->selectRenderer = $selectRenderer ?? SelectHtmlRenderer::instance();
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

        return $this->selectRenderer->render($view, ['multiple' => true] + $attributes);
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
