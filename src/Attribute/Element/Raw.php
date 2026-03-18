<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Leaf\NumberElementBuilder;

/**
 * Change the raw number mode
 *
 * In row mode, the number is parsed using a cast instead of use localized number parser
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->float('foo')->raw();
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Raw]
 *     private IntegerElement $foo;
 * }
 * </code>
 *
 * @see NumberElementBuilder::raw() The called method
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\Leaf\NumberElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Raw implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * Enable or disable raw mode for parsing numbers
         *
         * @readonly
         */
        private bool $flag = true,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->raw($this->flag);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('$?->raw(?);', [$name, $this->flag]);
    }
}
