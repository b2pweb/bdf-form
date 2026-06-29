<?php

namespace Bdf\Form\Attribute\Constraint;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\GenerateConfiguratorStrategy;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Constraint\Closure;
use Bdf\Form\ElementBuilderInterface;
use Nette\PhpGenerator\Literal;
use Override;
use Symfony\Component\Validator\Constraint;

use function is_object;
use function sprintf;

/**
 * Define a custom constraint for an element, using a validation method
 *
 * Note: prefer the usage of constraint class, declared as attribute
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
 *     #[CustomConstraint('validateFoo', message: 'Foo is invalid')]
 *     private IntegerElement $foo;
 *
 *     public function validateFoo($value, ElementInterface $input): bool
 *     {
 *         return $value % 5 > 2;
 *     }
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ElementBuilderInterface::satisfy() The called method
 * @see Constraint
 * @see Closure The used constraint
 * @see AsConstraint To annotate the method instead of the property
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class CallbackConstraint implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The method name to use as validator
         * Must be a public method declared on the form class
         *
         * Its prototype should be :
         * `public function ($value, ElementInterface $input): bool|string|array{code: string message: string}|null`
         *
         * - Return true, or null (nothing) for a valid input
         * - Return false for invalid input, with the default error message (or the declared one)
         * - Return string for a custom error message
         * - Return array with error message and code
         *
         * @var non-empty-string
         * @readonly
         */
        private string $methodName,
        /**
         * The error message to use
         * This option is used only if the validator return false, in other cases,
         * the message returned by the validator will be used
         *
         * @var string|null
         * @readonly
         */
        private ?string $message = null,
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        if (is_object($context)) {
            $constraint = new Closure($context->{$this->methodName}(...), $this->message);
        } else {
            $constraint = new Closure($context::{$this->methodName}(...), $this->message);
        }

        $builder->satisfy($constraint);
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $generator->use(Closure::class, 'ClosureConstraint');

        if (is_object($context)) {
            $closure = sprintf('$context->%s(...)', $this->methodName);
        } else {
            $closure = sprintf('%s::%s(...)', $generator->useAndSimplifyType($context), $this->methodName);
        }

        $parameters = $this->message !== null
            ? new Literal($closure . ', ?', [$this->message])
            : new Literal($closure)
        ;

        $generator->line('$?->satisfy(new ClosureConstraint(?));', [$name, $parameters]);
    }
}
