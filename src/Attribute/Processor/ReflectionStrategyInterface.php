<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
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
     * @param ProcessorMetadata $metadata Metadata for the current form
     * @param object|class-string $context The form instance or the DTO class name
     * @param FormBuilderInterface $builder Builder to configure
     *
     * @return void
     */
    public function onFormClass(ProcessorMetadata $metadata, object|string $context, FormBuilderInterface $builder): void;

    /**
     * Configure a button following the declared property
     * This method is only called one, even if the property is declared multiple times on ancestors
     * Only the child declaration will be processed
     *
     * @param ReflectionProperty $property The property to process
     * @param non-empty-string $name The button name
     * @param object|class-string $context The form instance or the DTO class name
     * @param FormBuilderInterface $builder Builder to configure
     * @param ProcessorMetadata $metadata Metadata for the current form
     *
     * @return void
     */
    public function onButtonProperty(ReflectionProperty $property, string $name, object|string $context, FormBuilderInterface $builder, ProcessorMetadata $metadata): void;

    /**
     * Configure an element following the declared property
     * This method is only called one, even if the property is declared multiple times on ancestors
     * Only the child declaration will be processed
     *
     * @param ElementPropertyMetadata $metadata Metadata for the element to process
     * @param object|class-string $context The form instance or the DTO class name
     * @param FormBuilderInterface $builder Builder to configure
     *
     * @return void
     */
    public function onElementProperty(ElementPropertyMetadata $metadata, object|string $context, FormBuilderInterface $builder): void;

    /**
     * @param ProcessorMetadata $metadata Metadata for the current form
     * @param object|class-string $context The form instance or the DTO class name
     *
     * @return PostConfigureInterface|null
     */
    public function onPostConfigure(ProcessorMetadata $metadata, object|string $context): ?PostConfigureInterface;
}
