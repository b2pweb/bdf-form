<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use ReflectionAttribute;

/**
 * Trait for implements method `generateCode()` using a simple builder method call
 * with the current attribute as parameter (i.e. generate the new expression according to the attribute class and parameters)
 *
 * @psalm-require-implements ElementAttributeProcessorInterface
 */
trait SimpleMethodCallGeneratorTrait
{
    /**
     * {@inheritdoc}
     */
    public function generateCode(string $name, AttributesProcessorGenerator $generator, ReflectionAttribute $attribute): void
    {
        /** @var class-string $constraint */
        $constraint = $attribute->getName();
        $generator->line('$?->?(?);', [$name, $this->methodName(), $generator->new($constraint, $attribute->getArguments())]);
    }

    /**
     * Called method name
     *
     * @return literal-string
     */
    abstract private function methodName(): string;
}
