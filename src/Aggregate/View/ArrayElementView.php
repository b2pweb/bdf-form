<?php

namespace Bdf\Form\Aggregate\View;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\View\ElementViewInterface;
use Bdf\Form\View\ElementViewTrait;
use Bdf\Form\View\FieldSetViewInterface;
use Bdf\Form\View\FieldSetViewTrait;
use Bdf\Form\View\FieldViewInterface;
use Bdf\Form\View\FieldViewRendererInterface;
use Bdf\Form\View\FieldViewTrait;
use Countable;
use IteratorAggregate;

/**
 * View object for the ArrayElement
 *
 * @see ArrayElement::view()
 */
final class ArrayElementView implements IteratorAggregate, FieldViewInterface, FieldSetViewInterface, Countable
{
    use ElementViewTrait;
    use FieldViewTrait;
    use FieldSetViewTrait {
        FieldSetViewTrait::hasError insteadof ElementViewTrait;
    }

    /**
     * ArrayElementView constructor.
     *
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @param string|null $error
     * @param ElementViewInterface[] $elements
     * @param bool $required
     * @param array $constraints
     */
    public function __construct(string $type, string $name, $value, ?string $error, array $elements, bool $required, array $constraints)
    {
        $this->type = $type;
        $this->name = $name;
        $this->error = $error;
        $this->value = $value;
        $this->elements = $elements;
        $this->required = $required;
        $this->constraints = $constraints;
    }

    /**
     * Check if the current element value is a CSV
     * If true, the element can be used as simple HTTP field
     *
     * @return bool
     */
    public function isCsv(): bool
    {
        return is_scalar($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultRenderer(): FieldViewRendererInterface
    {
        return ArrayElementViewRenderer::instance();
    }

    /**
     * Ignore property "attributes"
     *
     * @return array
     */
    public function __sleep()
    {
        return ['type', 'name', 'error', 'value', 'elements', 'required', 'constraints'];
    }
}
