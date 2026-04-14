<?php

namespace Bdf\Form\Attribute\Aggregate;

use Attribute;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Constraint\Satisfy;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Attribute\Processor\CodeGenerator\ObjectInstantiation;
use Bdf\Form\Child\ChildBuilderInterface;
use Override;
use Symfony\Component\Validator\Constraint;

/**
 * Add a constraint on the whole array element
 * Use Satisfy, or directly the constraint as attribute for add a constraint on one array item
 *
 * This attribute is equivalent to call :
 * <code>
 * $builder->array('values')->arrayConstraints(MyConstraint::class, $options);
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[ArrayConstraint(Unique::class, ['message' => 'My error'])]
 *     private ArrayElement $values;
 *
 *     // or on PHP 8.1
 *     #[ArrayConstraint(new Unique(['message' => 'My error']))]
 *     private ArrayElement $values;
 * }
 * </code>
 *
 * @see Satisfy Attribute for add constraint for items
 * @see ArrayElementBuilder::arrayConstraint() The called method
 * @see CallbackArrayConstraint Use for a custom method validation
 *
 * @implements ChildBuilderAttributeInterface<ArrayElementBuilder>
 * @api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class ArrayConstraint implements ChildBuilderAttributeInterface
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
         *
         * @var Constraint
         */
        private Constraint $constraint,
    ) {}

    #[Override]
    public function applyOnChildBuilder(object|string $context, ChildBuilderInterface $builder): void
    {
        $builder->arrayConstraint($this->constraint);
    }

    #[Override]
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, object|string $context): void
    {
        $constraint = ObjectInstantiation::promotedProperties($this->constraint)->render($generator);
        $generator->line('$?->arrayConstraint(?);', [$name, $constraint]);
    }
}
