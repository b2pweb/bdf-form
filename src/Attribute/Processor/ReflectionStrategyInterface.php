<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\ElementInterface;
use ReflectionClass;
use ReflectionProperty;

/**
 * Perform configuration action on each class or field
 *
 * This class can be considered as a visitor
 * It's responsive of apply configuration on forms, buttons and elements builders
 *
 * @see ReflectionProcessor The caller
 */
interface ReflectionStrategyInterface
{
    /**
     * Configure the form builder following the form class
     * This method will take the current attribute form class, but also all its ancestors until AttributeForm
     *
     * @param ReflectionClass<AttributeForm> $formClass Form class to use
     * @param AttributeForm $form The current form instance
     * @param FormBuilderInterface $builder Builder to configure
     * @param ProcessorMetadata $metadata Metadata for the current form
     *
     * @return void
     */
    public function onFormClass(ReflectionClass $formClass, AttributeForm $form, FormBuilderInterface $builder, ProcessorMetadata $metadata): void;

    /**
     * Configure a button following the declared property
     * This method is only called one, even if the property is declared multiple times on ancestors
     * Only the child declaration will be processed
     *
     * @param ReflectionProperty $property The property to process
     * @param non-empty-string $name The button name
     * @param AttributeForm $form The current form instance
     * @param FormBuilderInterface $builder Builder to configure
     * @param ProcessorMetadata $metadata Metadata for the current form
     *
     * @return void
     */
    public function onButtonProperty(ReflectionProperty $property, string $name, AttributeForm $form, FormBuilderInterface $builder, ProcessorMetadata $metadata): void;

    /**
     * Configure an element following the declared property
     * This method is only called one, even if the property is declared multiple times on ancestors
     * Only the child declaration will be processed
     *
     * @param ReflectionProperty $property The property to process
     * @param non-empty-string $name The element name
     * @param class-string<ElementInterface> $elementType The element type (i.e. the property type)
     * @param AttributeForm $form The current form instance
     * @param FormBuilderInterface $builder Builder to configure
     * @param ProcessorMetadata $metadata Metadata for the current form
     *
     * @return void
     */
    public function onElementProperty(ReflectionProperty $property, string $name, string $elementType, AttributeForm $form, FormBuilderInterface $builder, ProcessorMetadata $metadata): void;

    /**
     * @param ProcessorMetadata $metadata Metadata for the current form
     * @param AttributeForm $form The current form instance
     *
     * @return PostConfigureInterface|null
     */
    public function onPostConfigure(ProcessorMetadata $metadata, AttributeForm $form): ?PostConfigureInterface;
}
