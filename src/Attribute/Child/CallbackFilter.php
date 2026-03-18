<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;

/**
 * Add a filter on the child element, by using method
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->string('foo')->filter([$this, 'myTransformer']);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[CallbackFilter('filterFoo')]
 *     private IntegerElement $foo;
 *
 *     public function filterFoo($value, ChildInterface $child, $default): string
 *     {
 *         return hexdec($value);
 *     }
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ChildBuilderInterface::filter() The called method
 * @see ClosureFilter The used filter class
 * @see CallbackTransformer For use transformer in same way, but for http transformer intead of filter one
 * @see AsFilter To annotate a method as filter
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class CallbackFilter implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The method name to use as filter
         *
         * The method must be public and follow the signature `function (mixed $value, ElementInterface $input, mixed|null $default): mixed`
         *
         * @var non-empty-string
         * @readonly
         */
        private string $method,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->filter([$form, $this->method]);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->line('$?->filter([$form, ?]);', [$name, $this->method]);
    }
}
