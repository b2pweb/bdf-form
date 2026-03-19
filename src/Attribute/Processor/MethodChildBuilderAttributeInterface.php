<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use ReflectionMethod;

/**
 * Base type for attribute located on a method and applied on a child builder
 *
 * This attributes will act like it's define a {@see ChildBuilderAttributeInterface} on elements properties
 * listed by {@see MethodChildBuilderAttributeInterface::targetElements()}.
 *
 * @template E as \Bdf\Form\ElementBuilderInterface
 */
interface MethodChildBuilderAttributeInterface
{
    /**
     * List of elements names that the attribute can be applied on
     * Must be a list of property names
     *
     * @return list<non-empty-string>
     */
    public function targetElements(): array;

    /**
     * The actual object which will be applied on the child builder
     *
     * @param ReflectionMethod $method The method where the attribute is located
     * @return ChildBuilderAttributeInterface<E>
     */
    public function attribute(ReflectionMethod $method): ChildBuilderAttributeInterface;
}
