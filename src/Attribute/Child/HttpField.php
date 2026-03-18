<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Child\Http\ArrayOffsetHttpFields;
use LogicException;

/**
 * Define the HTTP field name for the child element
 *
 * This attributes allows to a form field with a property name that differs from the HTTP field name,
 * allowing to follow PHP naming conventions.
 *
 * Note: this attribute cannot be repeated
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->float('foo')->httpFields(new ArrayOffsetHttpFields('_foo'));
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[HttpField('package_length')]
 *     private FloatElement $packageLength;
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilder::httpFields() The called method
 * @see ArrayOffsetHttpFields The used HTTP fields implementation
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class HttpField implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The HTTP field name
         *
         * @var non-empty-string
         * @readonly
         */
        private string $name,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        if (!$builder instanceof ChildBuilder) {
            throw new LogicException('The HttpField attribute can only be used on a ChildBuilder instance');
        }

        $builder->httpFields(new ArrayOffsetHttpFields($this->name));
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->use(ArrayOffsetHttpFields::class);
        $generator->line('$?->httpFields(new ArrayOffsetHttpFields(?));', [$name, $this->name]);
    }
}
