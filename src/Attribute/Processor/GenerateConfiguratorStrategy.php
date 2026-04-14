<?php

namespace Bdf\Form\Attribute\Processor;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Attribute\Button\ButtonBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Nette\PhpGenerator\Closure;
use Nette\PhpGenerator\Literal;
use Override;
use ReflectionAttribute;
use ReflectionProperty;

use function is_object;

/**
 * Strategy for generate the processor class code
 */
final class GenerateConfiguratorStrategy implements ReflectionStrategyInterface
{
    private AttributesProcessorGenerator $generator;

    /**
     * @param non-empty-string $className The class name to generate. Must have a namespace
     * @throws \InvalidArgumentException If a namespace is not provided, or if the class name is not valid
     */
    public function __construct(string $className)
    {
        $this->generator = new AttributesProcessorGenerator($className);
    }

    #[Override]
    public function onFormClass(ProcessorMetadata $metadata, object|string $context, FormBuilderInterface $builder): void
    {
        $empty = true;

        foreach ($metadata->formAttributes as $attribute) {
            $attribute->generateCodeForFormBuilder($this->generator, $context);
            $empty = false;
        }

        if (!$empty) {
            $this->generator->line();
        }
    }

    #[Override]
    public function onButtonProperty(ReflectionProperty $property, string $name, object|string $context, FormBuilderInterface $builder, ProcessorMetadata $metadata): void
    {
        $this->generator->line('$builder->submit(?)', [$name]);

        foreach ($property->getAttributes(ButtonBuilderAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attribute->newInstance()->generateCodeForButtonBuilder($this->generator, $context);
        }

        $this->generator->line(";\n");
    }

    #[Override]
    public function onElementProperty(ElementPropertyMetadata $metadata, object|string $context, FormBuilderInterface $builder): void
    {
        $name = $metadata->name;
        $elementType = $this->generator->useAndSimplifyType($metadata->elementType);
        $this->generator->line('$? = $builder->add(?, ?::class);', [$name, $name, new Literal($elementType)]);

        foreach ($metadata->attributes as $attribute) {
            $attribute->generateCodeForChildBuilder($name, $this->generator, $context);
        }

        $this->generator->line(); // Add empty line
    }

    #[Override]
    public function onPostConfigure(ProcessorMetadata $metadata, object|string $context): ?PostConfigureInterface
    {
        if (!is_object($context)) {
            $this->generator->line('return null;');
            return null;
        }

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

        foreach ($elementProperties as $name => $propertyMetadata) {
            $property = $propertyMetadata->property;

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
}
