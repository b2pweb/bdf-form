<?php

namespace Bdf\Form\Attribute\Child;

use Attribute;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Element\CallbackTransformer;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;
use Nette\PhpGenerator\Literal;
use Override;

use function is_object;

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
final readonly class CallbackFilter implements ChildBuilderAttributeInterface
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
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        if (is_object($context)) {
            $builder->filter($context->{$this->method}(...));
        } else {
            $builder->filter($context::{$this->method}(...));
        }
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        if (is_object($context)) {
            $generator->line('$?->filter($context->?(...));', [$name, $this->method]);
        } else {
            $generator->line('$?->filter(?::?(...));', [$name, new Literal($generator->useAndSimplifyType($context)), $this->method]);
        }
    }
}
