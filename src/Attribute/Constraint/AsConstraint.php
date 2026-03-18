<?php

namespace Bdf\Form\Attribute\Constraint;

use Attribute;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\MethodChildBuilderAttributeInterface;
use Bdf\Form\Constraint\Closure;
use Bdf\Form\ElementBuilderInterface;
use ReflectionMethod;
use Symfony\Component\Validator\Constraint;

/**
 * Define the annotated method as a constraint for an element
 *
 * Its prototype should be :
 *   `public function ($value, ElementInterface $input): bool|string|array{code: string message: string}|null`
 *
 *  - Return true, or null (nothing) for a valid input
 *  - Return false for invalid input, with the default error message (or the declared one)
 *  - Return string for a custom error message
 *  - Return array with error message and code
 *
 * Note: prefer the usage of constraint class, declared as attribute directly on the element
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->integer('foo')->satisfy([$this, 'validateFoo'], 'Foo is invalid');
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[AsConstraint('foo', message: 'Foo is invalid')]
 *     public function validateFoo($value, ElementInterface $input): bool
 *     {
 *         return $value % 5 > 2;
 *     }
 * }
 * </code>
 *
 * @implements MethodChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ElementBuilderInterface::satisfy() The called method
 * @see Constraint
 * @see Closure The used constraint
 * @see CallbackConstraint For annotate the property instead of the method
 * @see AsArrayConstraint For use a constraint on an array element
 *
 * @api
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class AsConstraint implements MethodChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The element property name to which the constraint is applied
         *
         * @var literal-string
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
        return new CallbackConstraint($method->getName(), $this->message);
    }
}
