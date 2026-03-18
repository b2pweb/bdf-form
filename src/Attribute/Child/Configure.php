<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\MethodChildBuilderAttributeInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use ReflectionMethod;

/**
 * Define a custom configuration method for an element
 *
 * Use this attribute when other attributes cannot be used to configure the current element
 * Takes as argument the method name to use. This method must be declared into the form class with public visibility.
 *
 * It can also be used to directly annotate the configuration method.
 * In this case, it will take as argument the property name to configure.
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Configure('configureFoo')]
 *     private IntegerElement $foo;
 *
 *     public function configureFoo(ChildBuilderInterface $builder): void
 *     {
 *         $builder->min(5);
 *     }
 *
 *     // Or directly on the method (same as above)
 *     #[Configure('foo')]
 *     public function otherConfiguration(ChildBuilderInterface $builder): void
 *     {
 *         $builder->min(5);
 *     }
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 * @implements MethodChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Configure implements ChildBuilderAttributeInterface, MethodChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * Thet target of the configuration.
         *
         * In case of a property attribute, define the method name to use as configurator.
         * The method should follow the prototype `public function (ChildBuilderInterface $builder): void`
         *
         * In case of a method attribute, define the property name to configure.
         *
         * @var non-empty-string
         * @readonly
         */
        private string $target
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $form->{$this->target}($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('$form->?($?);', [$this->target, $name]);
    }

    /**
     * {@inheritdoc}
     */
    public function targetElements(): array
    {
        return [$this->target];
    }

    /**
     * {@inheritdoc}
     */
    public function attribute(ReflectionMethod $method): ChildBuilderAttributeInterface
    {
        return new self($method->getName());
    }
}
