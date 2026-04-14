<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\Attribute\Button\ButtonBuilderAttributeInterface;
use Override;
use ReflectionAttribute;
use ReflectionProperty;

use function is_object;

/**
 * Strategy for directly configure the form builder using attributes
 */
final class ConfigureFormBuilderStrategy implements ReflectionStrategyInterface
{
    #[Override]
    public function onFormClass(ProcessorMetadata $metadata, object|string $context, FormBuilderInterface $builder): void
    {
        foreach ($metadata->formAttributes as $attribute) {
            $attribute->applyOnFormBuilder($context, $builder);
        }
    }

    #[Override]
    public function onButtonProperty(ReflectionProperty $property, string $name, object|string $context, FormBuilderInterface $builder, ProcessorMetadata $metadata): void
    {
        $submitBuilder = $builder->submit($name);

        foreach ($property->getAttributes(ButtonBuilderAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->applyOnButtonBuilder($context, $submitBuilder);
        }
    }

    #[Override]
    public function onElementProperty(ElementPropertyMetadata $metadata, object|string $context, FormBuilderInterface $builder): void
    {
        $elementBuilder = $builder->add($metadata->name, $metadata->elementType);

        foreach ($metadata->attributes as $attribute) {
            $attribute->applyOnChildBuilder($context, $elementBuilder);
        }
    }

    #[Override]
    public function onPostConfigure(ProcessorMetadata $metadata, object|string $context): ?PostConfigureInterface
    {
        if (!is_object($context)) {
            return null;
        }

        return new PostConfigureReflectionSetProperties($metadata->elementProperties(), $metadata->buttonProperties());
    }
}
