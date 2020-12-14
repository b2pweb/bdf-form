<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Aggregate\Form;
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
 *
 * @todo button
 */
final class FormView implements IteratorAggregate, FieldSetViewInterface
{
    use ElementViewTrait;
    use FieldSetViewTrait {
        FieldSetViewTrait::hasError insteadof ElementViewTrait;
    }

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
}
