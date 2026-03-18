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
     * @param AttributeForm $form The current form instance
     * @param ButtonBuilderInterface $builder Builder to configure
     */
    public function applyOnButtonBuilder(AttributeForm $form, ButtonBuilderInterface $builder): void;

    /**
     * Generate the code corresponding to the attribute
     * The generated code must perform same action as `applyOnButtonBuilder()`
     *
     * @param AttributesProcessorGenerator $generator Code generator for the "configureBuilder" method
     * @param AttributeForm $form The current form instance
     *
     * @return void
     */
    public function generateCodeForButtonBuilder(AttributesProcessorGenerator $generator, AttributeForm $form): void;
}
