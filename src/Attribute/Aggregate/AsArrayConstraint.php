<?php

namespace Bdf\Form\Attribute\Aggregate;

use Attribute;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\MethodChildBuilderAttributeInterface;
use Bdf\Form\Constraint\Closure;
use ReflectionMethod;
use Symfony\Component\Validator\Constraint;

/**
 * Define the annotated method as a custom constraint for an array element
 *
 *  Its prototype should be :
 *    `public function ($value, ElementInterface $input): bool|string|array{code: string message: string}|null`
 *
 *   - Return true, or null (nothing) for a valid input
 *   - Return false for invalid input, with the default error message (or the declared one)
 *   - Return string for a custom error message
 *   - Return array with error message and code
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->array('foo')->arrayConstraint([$this, 'validateFoo'], 'Foo is invalid');
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     private ArrayElement $foo;
 *
 *     #[AsArrayConstraint('foo', message: 'Foo is invalid')]
 *     public function validateFoo(array $value, ElementInterface $input): bool
 *     {
 *         return count($value) % 5 > 2;
 *     }
 * }
 * </code>
 *
 * @see ArrayElementBuilder::arrayConstraint() The called method
 * @see Constraint
 * @see Closure The used constraint
 * @see ArrayConstraint Use for a class constraint
 * @see CallbackArrayConstraint To annotate the property instead of the method
 *
 * @implements MethodChildBuilderAttributeInterface<ArrayElementBuilder>
 *
 * @api
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class AsArrayConstraint implements MethodChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The element property name to which the constraint is applied
         *
         * @var non-empty-string
         * @readonly
         */
        private string $target,
        /**
         * The error message to use
         * This option is used only if the validator return false, in other cases,
         * the message returned by the validator will be used
         *
         * @var string|null
         * @readonly
         */
        private ?string $message = null,
    ) {
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
        return new CallbackArrayConstraint($method->getName(), $this->message);
    }
}
