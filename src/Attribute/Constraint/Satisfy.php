<?php

namespace Bdf\Form\Attribute\Constraint;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\ObjectInstantiation;
use Bdf\Form\Child\ChildBuilderInterface;
use InvalidArgumentException;
use Nette\PhpGenerator\Literal;
use Symfony\Component\Validator\Constraint;

use function is_object;
use function is_string;

/**
 * Define a custom constraint for an element, using a validation method
 *
 * Note: prefer use directly the constraint as attribute
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->integer('foo')->satisfy(MyConstraint::class, $options);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Satisfy(MyConstraint::class, ['foo' => 'bar'])]
 *     private IntegerElement $foo;
 *
 *     // or on PHP 8.1
 *     #[Satisfy(new MyConstraint(['foo' => 'bar']))]
 *     private IntegerElement $foo;
 * }
 * </code>
 *
 * @implements ChildBuilderAttributeInterface<\Bdf\Form\ElementBuilderInterface>
 *
 * @see ElementBuilderInterface::satisfy() The called method
 * @see Constraint
 *
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Satisfy implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The constraint
         *
         * You can use a class name, and provider arguments on the next parameter,
         * or directly use the constraint instance.
         *
         * When a constraint instance is used, in case of code generation,
         * the constructor parameters will be deduced from public properties of the constraint.
         * This may not work if the constraint has a complex constructor.
         * @var class-string<Constraint>|Constraint
         * @readonly
         */
        private string|Constraint $constraint,
        /**
         * Constraint's constructor options
         *
         * @var array|null|string
         * @readonly
         */
        private mixed $options = null
    ) {
        if (is_object($constraint) && $options !== null) {
            throw new InvalidArgumentException('Cannot use options with constraint instance');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->satisfy($this->constraint, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        if (is_string($this->constraint)) {
            $type = $generator->useAndSimplifyType($this->constraint);
            $generator->line('$?->satisfy(?::class, ?);', [$name, new Literal($type), $this->options]);
        } else {
            $constraint = ObjectInstantiation::singleArrayParameter($this->constraint)->render($generator);
            $generator->line('$?->satisfy(?);', [$name, $constraint]);
        }
    }
}
