<?php

namespace Bdf\Form\Attribute\Aggregate;

use Attribute;
use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Attribute\AttributeForm;
use Bdf\Form\Attribute\ChildBuilderAttributeInterface;
use Bdf\Form\Attribute\Processor\CodeGenerator\AttributesProcessorGenerator;
use Bdf\Form\Child\ChildBuilderInterface;
use Symfony\Component\Validator\Constraints\Count as CountConstraint;

/**
 * Add a Count constraint on the array element
 *
 * This attribute is equivalent to call one of those :
 * <code>
 * $builder->array('values')->count(['min' => 3]);
 * $builder->array('values')->arrayConstraint(new Count(min: 3));
 * </code>
 *
 * Usage:
 * <code>
 * class MyForm extends AttributeForm
 * {
 *     #[Count(min: 3, max: 42)]
 *     private ArrayElement $values;
 * }
 * </code>
 *
 * @see CountConstraint The used constraint
 * @see ArrayElementBuilder::arrayConstraint() The called method
 * @see ArrayElementBuilder::count() Equivalent method call
 *
 * @implements ChildBuilderAttributeInterface<ArrayElementBuilder>
 *
 * @api
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Count extends CountConstraint implements ChildBuilderAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function applyOnChildBuilder(AttributeForm $form, ChildBuilderInterface $builder): void
    {
        $builder->arrayConstraint($this);
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return CountConstraint::class . 'Validator';
    }

    /**
     * {@inheritdoc}
     */
    public function generateCodeForChildBuilder(string $name, AttributesProcessorGenerator $generator, AttributeForm $form): void
    {
        $defaultParameters = get_class_vars(CountConstraint::class);
        /** @var array{
         *     minMessage?: string,
         *     maxMessage?: string,
         *     exactMessage?: string,
         *     divisibleByMessage?: string,
         *     min?: int|null,
         *     max?: int|null,
         *     divisibleBy?: int|null,
         * } $parameters
         */
        $parameters = get_object_vars($this);

        foreach ($parameters as $paramName => $value) {
            if (!array_key_exists($paramName, $defaultParameters) || $value === $defaultParameters[$paramName]) {
                unset($parameters[$paramName]);
            }
        }

        $generator->line('$?->arrayConstraint(?);', [$name, $generator->new(CountConstraint::class, $parameters)]);
    }
}
