<?php

namespace Bdf\Form\Attribute\Element\Date;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;
use Override;

/**
 * Use a DateTimeImmutable type as date time on the element
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->dateTime('date')->immutable();
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[ImmutableDateTime]
 *     private DateTimeElement $foo;
 * }
 * </code>
 *
 * @see \Bdf\Form\Leaf\Date\DateTimeElementBuilder::immutable() The called method
 * @see DateTimeClass For use a custom class name
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\Leaf\Date\DateTimeElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ImmutableDateTime implements ChildBuilderAttributeInterface
{
    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $builder->immutable();
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $generator->line('$?->immutable();', [$name]);
    }
}
