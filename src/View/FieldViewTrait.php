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

    public private(set) string $name;
    public private(set) mixed $value;
    public private(set) bool $required = false;

    /**
     * @var array
     */
    private array $constraints = [];

    /**
     * @var ChoiceView[]|null
     */
    public private(set) ?array $choices = null;

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
    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
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
    public function render(?FieldViewRendererInterface $renderer = null): string
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
