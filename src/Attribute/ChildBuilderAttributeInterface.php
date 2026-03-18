<?php

namespace Bdf\Form\Attribute;

use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;

/**
 * Base attribute type for configure ChildBuilder
 * The attribute should be declared on the element property
 *
 * @see ChildBuilderInterface
 *
 * @template E as \Bdf\Form\ElementBuilderInterface
 */
interface ChildBuilderAttributeInterface
{
    /**
     * Configure the child builder
     *
     * @param AttributeForm $form The current form instance
     * @param ChildBuilderInterface<E> $builder The builder to configure
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void;

    /**
     * Generate the code corresponding to the attribute
     * The generated code must perform same action as `applyOnChildBuilder()`
     *
     * @param non-empty-string $name The variable name without $
     * @param AttributesProcessorGenerator $generator Code generator for the "configureBuilder" method
     * @param AttributeForm $form The current form instance
     *
     * @return void
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void;
}
