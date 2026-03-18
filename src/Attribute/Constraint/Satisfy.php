<?php

namespace Bdf\Form\Attribute\Constraint;

use Attribute;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\ObjectInstantiation;
use Bdf\Form\Child\ChildBuilderInterface;
use Nette\PhpGenerator\Literal;
use Override;
use Symfony\Component\Validator\Constraint;

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
final readonly class Satisfy implements ChildBuilderAttributeInterface
{
    public function __construct(
        /**
         * The constraint
         *
         * The constructor parameters will be deduced from public properties of the constraint.
         * This may not work if the constraint has a complex constructor.
         *
         * @var Constraint
         */
        private Constraint $constraint,
    ) {}

    #[Override]
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->satisfy($this->constraint);
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $constraint = ObjectInstantiation::promotedProperties($this->constraint)->render($generator);
        $generator->line('$?->satisfy(?);', [$name, $constraint]);
    }
}
