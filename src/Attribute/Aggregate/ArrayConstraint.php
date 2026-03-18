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
use InvalidArgumentException;
use Nette\PhpGenerator\Literal;
use Symfony\Component\Validator\Constraint;

use function is_object;
use function is_string;

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
final class ArrayConstraint implements ChildBuilderAttributeInterface
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
         * @var class-string<Constraint>|Constraint
         * @readonly
         */
        private string|Constraint $constraint,
        /**
         * Constraint's constructor options
         *
         * @var mixed|null
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
        $builder->arrayConstraint($this->constraint, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        if (is_string($this->constraint)) {
            $constraint = $generator->useAndSimplifyType($this->constraint);
            $generator->line('$?->arrayConstraint(?::class, ?);', [$name, new Literal($constraint), $this->options]);
        } else {
            $constraint = ObjectInstantiation::singleArrayParameter($this->constraint)->render($generator);
            $generator->line('$?->arrayConstraint(?);', [$name, $constraint]);
        }
    }
}
