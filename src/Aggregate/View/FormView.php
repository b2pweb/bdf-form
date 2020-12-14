<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Aggregate\Form;
use Bdf\Form\Button\View\ButtonViewInterface;
use Bdf\Form\View\ElementViewInterface;
use Bdf\Form\View\ElementViewTrait;
use Bdf\Form\View\FieldSetViewInterface;
use Bdf\Form\View\FieldSetViewTrait;
use IteratorAggregate;

/**
 * View for a form element
 * Works for root and embedded forms
 *
 * @see Form::view()
 */
final class FormView implements IteratorAggregate, FieldSetViewInterface
{
    use ElementViewTrait;
    use FieldSetViewTrait {
        FieldSetViewTrait::hasError insteadof ElementViewTrait;
    }

    /**
     * @var ButtonViewInterface[]
     */
    private $buttons = [];

    /**
     * FormView constructor.
     *
     * @param string $type
     * @param string|null $error
     * @param ElementViewInterface[] $elements
     */
    public function __construct(string $type, ?string $error, array $elements)
    {
        $this->type = $type;
        $this->error = $error;
        $this->elements = $elements;
    }

    /**
     * {@inheritdoc}
     *
     * @return ElementViewInterface|ButtonViewInterface
     */
    public function offsetGet($offset)
    {
        return $this->elements[$offset] ?? $this->buttons[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->elements[$offset]) || isset($this->buttons[$offset]);
    }

    /**
     * Get all available buttons
     *
     * @return ButtonViewInterface[]
     */
    public function buttons(): array
    {
        return $this->buttons;
    }

    /**
     * Get a button by its name
     *
     * @param string $name
     *
     * @return ButtonViewInterface|null
     */
    public function button(string $name): ?ButtonViewInterface
    {
        return $this->buttons[$name] ?? null;
    }

    /**
     * Change the form type
     * Used internally by CustomForm
     *
     * @param string $type The form class name
     * @internal
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Set form buttons
     *
     * @param ButtonViewInterface[] $buttons
     * @internal
     */
    public function setButtons(array $buttons): void
    {
        $this->buttons = $buttons;
    }
}
