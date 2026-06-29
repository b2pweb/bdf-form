<?php

namespace Bdf\Form\Attribute\Button;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Button\ButtonBuilderInterface;
use Override;

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
final readonly class Value implements ButtonBuilderAttributeInterface
{
    public function __construct(
        /**
         * The button HTTP value
         * @readonly
         */
        private string $value,
    ) {}

    #[Override]
    public function applyOnButtonBuilder(object|string $context, ButtonBuilderInterface $builder): void
    {
        $builder->value($this->value);
    }

    #[Override]
    public function generateCodeForButtonBuilder(AttributesProcessorGenerator $generator, object|string $context): void
    {
        $generator->line('    ->value(?)', [$this->value]);
    }
}
