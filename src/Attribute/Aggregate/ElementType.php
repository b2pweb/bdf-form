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

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $configurator = $this->configurator !== null ? [$form, $this->configurator] : null;
        $builder->element($this->elementType, $configurator);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $elementType = new Literal($generator->useAndSimplifyType($this->elementType));

        if ($this->configurator !== null) {
            $generator->line('$?->element(?::class, [$form, ?]);', [$name, $elementType, $this->configurator]);
        } else {
            $generator->line('$?->element(?::class);', [$name, $elementType]);
        }
    }
}
