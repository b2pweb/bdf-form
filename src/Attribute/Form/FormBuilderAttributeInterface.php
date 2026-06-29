<?php

namespace Bdf\Form\Attribute\Form;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Nette\PhpGenerator\Method;

/**
 * Attribute type for configure a form builder
 * Those attributes should be declared on the class declaration
 *
 * @see FormBuilderInterface The configured builder
 */
interface FormBuilderAttributeInterface
{
    /**
     * Configure the given builder
     *
     * @param object|class-string $context The form instance or the DTO class name
     * @param FormBuilderInterface $builder The form builder
     */
    public function applyOnFormBuilder(object|string $context, FormBuilderInterface $builder): void;

    /**
     * Generate the code corresponding to the attribute
     * The generated code must perform same action as `applyOnFormBuilder()`
     *
     * @param AttributesProcessorGenerator $generator Code generator for the "configureBuilder" method
     * @param object|class-string $context The form instance or the DTO class name
     *
     * @return void
     */
    public function generateCodeForFormBuilder(AttributesProcessorGenerator $generator, object|string $context): void;
}
