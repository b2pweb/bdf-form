<?php

namespace Bdf\Form\Registry;

use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
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
     * @param mixed $filter
     *
     * @return FilterInterface
     */
    public function filter($filter): FilterInterface;

    /**
     * Create the constraint
     *
     * @param mixed $constraint
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
     * @param string $element The element class name
     * @param string $name The child name
     *
     * @return ChildBuilderInterface
     */
    public function childBuilder(string $element, string $name): ChildBuilderInterface;

    /**
     * Create the element build for an element
     *
     * @param string $element The element class name
     *
     * @return ElementBuilderInterface
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
