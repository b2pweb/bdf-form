<?php

namespace Bdf\Form\View;

use Bdf\Form\Choice\ChoiceView;

/**
 * Implements @see FieldViewInterface
 *
 * @psalm-require-implements FieldViewInterface
 */
trait FieldViewTrait
{
    use RenderableTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var array
     */
    private $constraints = [];

    /**
     * @var ChoiceView[]|null
     */
    private $choices;

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
    public function value()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function required(): bool
    {
        return $this->required;
    }

    /**
     * {@inheritdoc}
     */
    public function constraints(): array
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<array-key, ChoiceView>|null
     */
    public function choices(): ?array
    {
        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function render(FieldViewRendererInterface $renderer = null): string
    {
        return ($renderer ?? $this->defaultRenderer())->render($this, $this->attributes);
    }

    /**
     * Get the default renderer to use for the current view implementation
     *
     * @return FieldViewRendererInterface
     */
    abstract protected function defaultRenderer(): FieldViewRendererInterface;
}
