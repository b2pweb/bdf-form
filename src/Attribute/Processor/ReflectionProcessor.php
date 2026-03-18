<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\ElementInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Base processor using reflection for extract properties and attributes
 *
 * The configuration action will be delegated to the ReflectionStrategyInterface
 * This implementation is only responsive of iterate over class hierarchy and properties
 *
 * @api
 */
final class ReflectionProcessor implements AttributesProcessorInterface
{
    /**
     * Strategy to use on each field / class
     *
     * @var ReflectionStrategyInterface
     */
    private ReflectionStrategyInterface $strategy;

    /**
     * @param ReflectionStrategyInterface $strategy
     */
    public function __construct(ReflectionStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function configureBuilder(AttributeForm $form, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $metadata = new ProcessorMetadata();

        // First iterate over methods to build the metadata
        $this->registerMethodsMetadata($form, $metadata);

        foreach ($this->iterateClassHierarchy($form) as $formClass) {
            $this->strategy->onFormClass($formClass, $form, $builder, $metadata);

            foreach ($formClass->getProperties() as $property) {
                $name = $property->getName();

                if (
                    !$property->hasType()
                    || !$property->getType() instanceof ReflectionNamedType
                    || $metadata->hasProperty($name)
                ) {
                    continue;
                }

                $elementType = $property->getType()->getName();
                PHP_VERSION_ID >= 80100 or $property->setAccessible(true);

                if ($elementType === ButtonInterface::class) {
                    $metadata->addButtonProperty($name, $property);
                    $this->strategy->onButtonProperty($property, $name, $form, $builder, $metadata);
                } elseif (is_subclass_of($elementType, ElementInterface::class)) {
                    $metadata->addElementProperty($name, $property);
                    $this->strategy->onElementProperty($property, $name, $elementType, $form, $builder, $metadata);
                }
            }
        }

        return $this->strategy->onPostConfigure($metadata, $form);
    }

    /**
     * Iterate over the class hierarchy of the annotation form
     * The iteration will start with the form class, and end with the AttributeForm class (excluded)
     *
     * @param AttributeForm $form
     *
     * @return iterable<ReflectionClass<AttributeForm>>
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    private function iterateClassHierarchy(AttributeForm $form): iterable
    {
        for ($reflection = new ReflectionClass($form); $reflection->getName() !== AttributeForm::class; $reflection = $reflection->getParentClass()) {
            yield $reflection;
        }
    }

    /**
     * Fill the metadata from methods attributes
     *
     * @param AttributeForm $form
     * @param ProcessorMetadata $metadata
     *
     * @return void
     */
    private function registerMethodsMetadata(AttributeForm $form, ProcessorMetadata $metadata): void
    {
        foreach ((new ReflectionClass($form))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(MethodChildBuilderAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var MethodChildBuilderAttributeInterface $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                foreach ($attributeInstance->targetElements() as $target) {
                    $metadata->addChildAttribute($target, $attributeInstance->attribute($method));
                }
            }
        }
    }
}
