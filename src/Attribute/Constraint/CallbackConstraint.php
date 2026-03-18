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
use Symfony\Component\Validator\Constraint;

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
final class CallbackConstraint implements ChildBuilderAttributeInterface
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
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $constraint = new Closure(['callback' => [$form, $this->methodName]]);

        if ($this->message !== null) {
            $constraint->message = $this->message;
        }

        $builder->satisfy($constraint);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $generator->use(Closure::class, 'ClosureConstraint');

        $parameters = $this->message !== null
            ? new Literal("['callback' => [\$form, ?], 'message' => ?]", [$this->methodName, $this->message])
            : new Literal('[$form, ?]', [$this->methodName])
        ;

        $generator->line('$?->satisfy(new ClosureConstraint(?));', [$name, $parameters]);
    }
}
