<?php

namespace Bdf\Form\Registry;

use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\Transformer\TransformerInterface;
use LogicException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Registry for form components
 */
interface RegistryInterface
{
    /**
     * Create the filter
     *
     * @param FilterInterface|callable|string $filter
     *
     * @return FilterInterface
     */
    public function filter($filter): FilterInterface;

    /**
     * Create the constraint
     *
     * @param Constraint|callable|array|string $constraint
     *
     * @return Constraint
     */
    public function constraint($constraint): Constraint;

    /**
     * Create a view transformer
     *
     * @param callable|TransformerInterface|DataTransformerInterface $transformer
     *
     * @return TransformerInterface
     *
     * @throws LogicException If the transformer is invalid
     */
    public function transformer($transformer): TransformerInterface;

    /**
     * Create the child builder
     *
     * Usage:
     * <code>
     * $registry->childBuilder(Form::class, 'embedded'); // Create an embedded form builder
     * $registry->childBuilder(MyCustomForm::class, 'custom'); // Create a custom embedded form builder
     * $registry->childBuilder(IntegerElement::class, 'foo'); // Create builder for integer element "foo"
     * </code>
     *
     * @param class-string<E> $element The element class name
     * @param string $name The child name
     *
     * @return ChildBuilderInterface<ElementBuilderInterface<E>>
     *
     * @template E as \Bdf\Form\ElementInterface
     *
     * @throws \InvalidArgumentException When cannot found the element builder
     */
    public function childBuilder(string $element, string $name): ChildBuilderInterface;

    /**
     * Create the element build for an element
     *
     * Usage:
     * <code>
     * $registry->elementBuilder(Form::class); // Create the form builder
     * $registry->elementBuilder(MyCustomForm::class); // Should also works with custom forms
     * $registry->elementBuilder(IntegerElement::class); // For a leaf element
     * </code>
     *
     * @param class-string<E> $element The element class name
     *
     * @return ElementBuilderInterface<E>
     *
     * @template E as \Bdf\Form\ElementInterface
     *
     * @throws \InvalidArgumentException When cannot found the element builder
     */
    public function elementBuilder(string $element): ElementBuilderInterface;

    /**
     * Create the build for a button
     *
     * @param string $name The button name
     *
     * @return ButtonBuilderInterface
     */
    public function buttonBuilder(string $name): ButtonBuilderInterface;
}
