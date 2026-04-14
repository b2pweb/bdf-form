<?php

namespace Bdf\Form\Attribute\Aggregate;

use Attribute;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementInterface;
use Nette\PhpGenerator\Literal;
use Override;

use function is_object;
use function is_string;

/**
 * Attribute for define the array element type
 * You can also define a configuration method (not required)
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call one of those :
 * <code>
 * $builder->array('values')->element(IntegerElement::class, [$this, 'myConfigurator']);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[ElementType(IntegerElement::class, 'configureValueItem')]
 *     private ArrayElement $values;
 *
 *     // The method must be public and take the builder as parameter
 *     public function configureValueItem(IntegerElementBuilder $builder)
 *     {
 *         $builder->min(5); // Configure the element
 *     }
 * }
 * </code>
 *
 * @see ArrayElementBuilder::element() The called method
 *
 * @implements ChildBuilderAttributeInterface<ArrayElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ElementType implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The form element class name
         *
         * @var class-string<ElementInterface>
         * @readonly
         */
        private string $elementType,
        /**
         * The element configuration method name
         * This method must be defined on the form class, and with public visibility
         *
         * @var literal-string|null
         * @readonly
         */
        private ?string $configurator = null
    ) {
    }

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $configurator = match (true) {
            $this->configurator !== null && is_object($context) => $context->{$this->configurator}(...),
            $this->configurator !== null && is_string($context) => $context::{$this->configurator}(...),
            default => null,
        };
        $builder->element($this->elementType, $configurator);
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $elementType = new Literal($generator->useAndSimplifyType($this->elementType));

        match (true) {
            $this->configurator !== null && is_object($context) => $generator->line('$?->element(?::class, $context->?(...));', [$name, $elementType, $this->configurator]),
            $this->configurator !== null && is_string($context) => $generator->line('$?->element(?::class, ?::?(...));', [$name, $elementType, new Literal($generator->useAndSimplifyType($context)), $this->configurator]),
            default => $generator->line('$?->element(?::class);', [$name, $elementType]),
        };
    }
}
