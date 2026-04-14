<?php

namespace Bdf\Form\Attribute\Element;

use Attribute;
use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;
use Override;

/**
 * Define the element as required
 *
 * This attribute provides a more consistent way with builder method to define required element, instead of adding NotBlank constraint.
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->float('foo')->required();
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Required]
 *     private IntegerElement $foo;
 * }
 * </code>
 *
 * @see AbstractElementBuilder::required() The called method
 *
 * @implements ChildBuilderAttributeInterface<AbstractElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Required implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * Define the message to display if the element is not filled
         * If not set, the default message will be used
         *
         * @readonly
         */
        private ?string $message = null,
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $builder->required($this->message);
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $generator->line('$?->required(?);', [$name, $this->message]);
    }
}
