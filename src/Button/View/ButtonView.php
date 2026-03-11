<?php

namespace Bdf\Form\Button\View;

use Bdf\Form\View\RenderableTrait;
use Override;

/**
 * Base view object for buttons
 *
 * <code>
 * echo $view->class('btn btn-primary'); // <input type="submit" name="btn" value="ok" />
 * echo $view->class('btn btn-primary')->inner('Process'); // <button type="submit" name="btn" value="ok">Process</button>
 * </code>
 */
final class ButtonView implements ButtonViewInterface
{
    use RenderableTrait;

    public private(set) string $name;
    public private(set) string $value;
    public private(set) bool $clicked;

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

    #[Override]
    public function name(): string
    {
        return $this->name;
    }

    #[Override]
    public function value(): string
    {
        return $this->value;
    }

    #[Override]
    public function clicked(): bool
    {
        return $this->clicked;
    }

    #[Override]
    public function render(?ButtonViewRendererInterface $renderer = null): string
    {
        return ($renderer ?? ButtonViewRenderer::instance())->render($this, $this->attributes);
    }
}
