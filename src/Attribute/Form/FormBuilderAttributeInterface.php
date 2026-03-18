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
     * @param AttributeForm $form The form to configure
     * @param FormBuilderInterface $builder The form builder
     */
    public function applyOnFormBuilder(AttributeForm $form, FormBuilderInterface $builder): void;

    /**
     * Generate the code corresponding to the attribute
     * The generated code must perform same action as `applyOnFormBuilder()`
     *
     * @param AttributesProcessorGenerator $generator Code generator for the "configureBuilder" method
     * @param AttributeForm $form The current form instance
     *
     * @return void
     */
    public function generateCodeForFormBuilder(AttributesProcessorGenerator $generator, AttributeForm $form): void;
}
