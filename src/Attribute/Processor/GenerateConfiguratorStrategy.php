<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Button\ButtonBuilderAttributeInterface;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Form\FormBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\Element\ConstraintAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\ElementAttributeProcessorInterface;
use Bdf\Form\Attribute\Processor\Element\ExtractorAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\FilterAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\HydratorAttributeProcessor;
use Bdf\Form\Attribute\Processor\Element\TransformerAttributeProcessor;
use Nette\PhpGenerator\Closure;
use Nette\PhpGenerator\Literal;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

/**
 * Strategy for generate the processor class code
 */
final class GenerateConfiguratorStrategy implements ReflectionStrategyInterface
{
    private AttributesProcessorGenerator $generator;

    /**
     * @var list<ElementAttributeProcessorInterface>
     */
    private array $elementProcessors = [];

    /**
     * @param non-empty-string $className The class name to generate. Must have a namespace
     * @throws \InvalidArgumentException If a namespace is not provided, or if the class name is not valid
     */
    public function __construct(string $className)
    {
        $this->generator = new AttributesProcessorGenerator($className);

        $this->registerElementAttributeProcessor(new ConstraintAttributeProcessor());
        $this->registerElementAttributeProcessor(new FilterAttributeProcessor());
        $this->registerElementAttributeProcessor(new TransformerAttributeProcessor());
        $this->registerElementAttributeProcessor(new HydratorAttributeProcessor());
        $this->registerElementAttributeProcessor(new ExtractorAttributeProcessor());
    }

    /**
     * {@inheritdoc}
     */
    public function onFormClass(ReflectionClass $formClass, AttributeForm $form, FormBuilderInterface $builder, ProcessorMetadata $metadata): void
    {
        $empty = true;

        foreach ($formClass->getAttributes(FormBuilderAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->generateCodeForFormBuilder($this->generator, $form);
            $empty = false;
        }

        if (!$empty) {
            $this->generator->line();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onButtonProperty(ReflectionProperty $property, string $name, AttributeForm $form, FormBuilderInterface $builder, ProcessorMetadata $metadata): void
    {
        $this->generator->line('$builder->submit(?)', [$name]);

        foreach ($property->getAttributes(ButtonBuilderAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->generateCodeForButtonBuilder($this->generator, $form);
        }

        $this->generator->line(";\n");
    }

    /**
     * {@inheritdoc}
     */
    public function onElementProperty(ReflectionProperty $property, string $name, string $elementType, AttributeForm $form, FormBuilderInterface $builder, ProcessorMetadata $metadata): void
    {
        $elementType = $this->generator->useAndSimplifyType($elementType);
        $this->generator->line('$? = $builder->add(?, ?::class);', [$name, $name, new Literal($elementType)]);

        foreach ($property->getAttributes() as $attribute) {
            if (is_subclass_of($attribute->getName(), ChildBuilderAttributeInterface::class)) {
                /** @var ChildBuilderAttributeInterface $attributeInstance */
                $attributeInstance = $attribute->newInstance();
                $attributeInstance->generateCodeForChildBuilder($name, $this->generator, $form);
                continue;
            }

            foreach ($this->elementProcessors as $configurator) {
                if (is_subclass_of($attribute->getName(), $configurator->type())) {
                    $configurator->generateCode($name, $this->generator, $attribute);
                }
            }
        }

        foreach ($metadata->registeredChildAttributes($name) as $attribute) {
            $attribute->generateCodeForChildBuilder($name, $this->generator, $form);
        }

        $this->generator->line(); // Add empty line
    }

    /**
     * {@inheritdoc}
     */
    public function onPostConfigure(ProcessorMetadata $metadata, AttributeForm $form): ?PostConfigureInterface
    {
        $this->generator->line('return $this;');

        $method = $this->generator
            ->implements(PostConfigureInterface::class)
            ->implementsMethod(PostConfigureInterface::class, 'postConfigure')
        ;

        $elementProperties = $metadata->elementProperties();
        $buttonProperties = $metadata->buttonProperties();

        if (!empty($buttonProperties)) {
            $method->addBody('$root = $form->root();');
        }

        $scopedProperties = [];

        foreach ($elementProperties as $name => $property) {
            if ($property->isPublic()) {
                $method->addBody('$form->? = $inner[?]->element();', [$name, $name]);
            } else {
                $scopedProperties[$property->getDeclaringClass()->getName()][$name] = ['$form->? = $inner[?]->element();', [$name, $name]];
            }
        }

        foreach ($buttonProperties as $name => $property) {
            if ($property->isPublic()) {
                $method->addBody('$form->? = $root->button(?);', [$name, $name]);
            } else {
                $scopedProperties[$property->getDeclaringClass()->getName()][$name] = ['$form->? = $root->button(?);', [$name, $name]];
            }
        }

        foreach ($scopedProperties as $className => $lines) {
            $closure = new Closure();
            $closure->addUse('inner');
            $closure->addUse('form');

            if (!empty($buttonProperties)) {
                $closure->addUse('root');
            }

            array_map(fn ($line) => $closure->addBody(...$line), $lines);

            $method->addBody(
                '(\Closure::bind(?, null, ?::class))();',
                [
                    new Literal($this->generator->printer()->printClosure($closure)),
                    new Literal($this->generator->useAndSimplifyType($className)),
                ]
            );
        }

        return null;
    }

    /**
     * Print the generated class code
     *
     * @return string
     *
     * @see AttributesProcessorGenerator::print()
     */
    public function code(): string
    {
        return $this->generator->print();
    }

    /**
     * Register a new processor for element attributes
     *
     * @param ElementAttributeProcessorInterface<T> $processor
     *
     * @return void
     *
     * @template T as object
     */
    private function registerElementAttributeProcessor(ElementAttributeProcessorInterface $processor): void
    {
        $this->elementProcessors[] = $processor;
    }
}
