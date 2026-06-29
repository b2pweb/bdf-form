<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Form\FormBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\Element\ConstraintAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\ElementAttributeChildBuilderAdapter;
use Bdf\Form\Attribute\Processor\Element\ElementAttributeProcessorInterface;
use Bdf\Form\Attribute\Processor\Element\ExtractorAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\FilterAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\HydratorAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\TransformerAttributeProcessor;
use Bdf\Form\Button\ButtonInterface;
use Bdf\Form\ElementInterface;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

use function assert;
use function is_string;
use function is_subclass_of;

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
     * @var list<ElementAttributeProcessorInterface>
     */
    private array $elementProcessors = [];

    public function __construct(
        /**
         * Strategy to use on each field / class
         *
         * @var ReflectionStrategyInterface
         */
        private readonly ReflectionStrategyInterface $strategy,

        /**
         * Function for mapping the property type to the corresponding element type
         * Takes as parameter the property type and returns the mapped property type.
         *
         * For example, the function can return `StringElement::class` when the property type
         * is `string` to register a new form field.
         *
         * @var (callable(string):string)|null
         */
        private readonly mixed $elementTypeMapper = null,

        /**
         * Function for post process the metadata after the extraction
         * This allows to add attributes to properties or on form itself
         *
         * Takes as parameter the metadata and context.
         *
         * @var (callable(ProcessorMetadata, class-string|object):void)|null
         */
        private readonly mixed $metadataPostProcess = null,
    ) {
        $this->elementProcessors[] = new ConstraintAttributeProcessor();
        $this->elementProcessors[] = new FilterAttributeProcessor();
        $this->elementProcessors[] = new TransformerAttributeProcessor();
        $this->elementProcessors[] = new HydratorAttributeProcessor();
        $this->elementProcessors[] = new ExtractorAttributeProcessor();
    }

    #[Override]
    public function configureBuilder(string|object $context, FormBuilderInterface $builder): ?PostConfigureInterface
    {
        $metadata = new ProcessorMetadata();
        $contextClassName = is_string($context) ? $context : $context::class;

        foreach ($this->iterateClassHierarchy($contextClassName) as $formClass) {
            foreach ($formClass->getAttributes(FormBuilderAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $metadata->addFormAttribute($attribute->newInstance());
            }

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

                if ($this->elementTypeMapper !== null) {
                    $elementType = ($this->elementTypeMapper)($elementType);
                }

                if ($elementType === ButtonInterface::class) {
                    $metadata->addButtonProperty($name, $property);
                    continue;
                }

                if (is_subclass_of($elementType, ElementInterface::class)) {
                    $attributes = [];

                    foreach ($property->getAttributes() as $attribute) {
                        if (is_subclass_of($attribute->name, ChildBuilderAttributeInterface::class)) {
                            /** @var ChildBuilderAttributeInterface */
                            $attributes[] = $attribute->newInstance();
                            continue;
                        }

                        foreach ($this->elementProcessors as $configurator) {
                            if (
                                $attribute->name === $configurator->type()
                                || is_subclass_of($attribute->name, $configurator->type())
                            ) {
                                $attributes[] = new ElementAttributeChildBuilderAdapter($configurator, $attribute);
                            }
                        }
                    }

                    $metadata->addElementProperty(new ElementPropertyMetadata($name, $property, $elementType, $attributes));
                }
            }
        }

        if ($this->metadataPostProcess !== null) {
            ($this->metadataPostProcess)($metadata, $context);
        }

        $this->registerMethodsMetadata($contextClassName, $metadata);

        $this->strategy->onFormClass($metadata, $context, $builder);

        foreach ($metadata->elementProperties() as $elementProperty) {
            $this->strategy->onElementProperty($elementProperty, $context, $builder);
        }

        foreach ($metadata->buttonProperties() as $buttonProperty) {
            assert($buttonProperty->name !== '');
            $this->strategy->onButtonProperty($buttonProperty, $buttonProperty->name, $context, $builder, $metadata);
        }

        return $this->strategy->onPostConfigure($metadata, $context);
    }

    /**
     * Iterate over the class hierarchy of the annotation form
     * The iteration will start with the form class, and end with the AttributeForm class (excluded)
     *
     * @param class-string $form
     *
     * @return iterable<ReflectionClass>
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    private function iterateClassHierarchy(string $form): iterable
    {
        for ($reflection = new ReflectionClass($form); $reflection && $reflection->getName() !== AttributeForm::class; $reflection = $reflection->getParentClass()) {
            yield $reflection;
        }
    }

    /**
     * Fill the metadata from methods attributes
     *
     * @param class-string $form
     * @param ProcessorMetadata $metadata
     *
     * @return void
     */
    private function registerMethodsMetadata(string $form, ProcessorMetadata $metadata): void
    {
        foreach (new ReflectionClass($form)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
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
