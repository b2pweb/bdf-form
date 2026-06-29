<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;use Bdf\Form\Attribute\Processor\MethodChildBuilderAttributeInterface;use Bdf\Form\Child\ChildBuilderInterface;
use Override;
use ReflectionAttribute;

use function is_subclass_of;

/**
 * Adapt an attribute with its {@see ElementAttributeProcessorInterface} to be used as {@see ChildBuilderAttributeInterface}
 *
 * @template T as object
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 */
final readonly class ElementAttributeChildBuilderAdapter implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * @var ElementAttributeProcessorInterface<T>
         */
        private ElementAttributeProcessorInterface $processor,

        /**
         * @var ReflectionAttribute<T>
         */
        private ReflectionAttribute $attribute,
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $this->processor->process($builder, $this->attribute->newInstance());
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $this->processor->generateCode($name, $generator, $this->attribute);
    }

    /**
     * Check if the current attribute is an instance of the given type
     *
     * @param class-string $type
     * @return bool
     */
    public function is(string $type): bool
    {
        return
            $this->attribute->getName() === $type
            || is_subclass_of($this->attribute->getName(), $type)
        ;
    }
}
