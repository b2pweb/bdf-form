<?php

namespace Bdf\Form\Attribute\Button;

use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Button\ButtonBuilderInterface;

/**
 * Base type for attributes used for configure buttons
 * The attribute must be defined on the button property
 *
 * @see ButtonBuilderInterface The configured builder
 */
interface ButtonBuilderAttributeInterface
{
    /**
     * Configure the button builder
     *
     * @param object|class-string $context The form instance or the DTO class name
     * @param ButtonBuilderInterface $builder Builder to configure
     */
    public function applyOnButtonBuilder(object|string $context, ButtonBuilderInterface $builder): void;

    /**
     * Generate the code corresponding to the attribute
     * The generated code must perform same action as `applyOnButtonBuilder()`
     *
     * @param AttributesProcessorGenerator $generator Code generator for the "configureBuilder" method
     * @param object|class-string $context The form instance or the DTO class name
     *
     * @return void
     */
    public function generateCodeForButtonBuilder(AttributesProcessorGenerator $generator, object|string $context): void;
}
