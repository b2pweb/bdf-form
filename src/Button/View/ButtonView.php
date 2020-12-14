<?php

namespace Bdf\Form\Button\View;

use Bdf\Form\View\RenderableTrait;

/**
 * Base view object for buttons
 */
final class ButtonView implements ButtonViewInterface
{
    use RenderableTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * @var bool
     */
    private $clicked;

    /**
     * ButtonView constructor.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $clicked
     */
    public function __construct(string $name, string $value, bool $clicked)
    {
        $this->name = $name;
        $this->value = $value;
        $this->clicked = $clicked;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function clicked(): bool
    {
        return $this->clicked;
    }

    /**
     * {@inheritdoc}
     */
    public function render(?ButtonViewRendererInterface $renderer = null): string
    {
        return ($renderer ?? ButtonViewRenderer::instance())->render($this, $this->attributes);
    }
}
