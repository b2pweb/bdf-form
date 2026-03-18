<?php

namespace Bdf\Form\Attribute\Button;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Button\ButtonBuilderInterface;

/**
 * Attribute for define the button value, used to check if the button is clicked
 *
 * Note: this attribute is not repeatable
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->button('btn')->value('Foo');
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Value('Foo')]
 *     private ButtonInterface $btn;
 * }
 * </code>
 *
 * @see ButtonBuilderInterface::value() The called method
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Value implements ButtonBuilderAttributeInterface
{
    public function __construct(
        /**
         * The button HTTP value
         * @readonly
         */
        private string $value,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnButtonBuilder(AttributeForm $form, ButtonBuilderInterface $builder): void
    {
        $builder->value($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForButtonBuilder(AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('    ->value(?)', [$this->value]);
    }
}
