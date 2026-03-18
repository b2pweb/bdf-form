<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Add the constraint by calling satisfy
 *
 * @see ElementBuilderInterface::satisfy()
 * @see Constraint
 *
 * @implements ElementAttributeProcessorInterface<Constraint>
 */
final class ConstraintAttributeProcessor implements ElementAttributeProcessorInterface
{
    use SimpleMethodCallGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return Constraint::class;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->satisfy($attribute);
    }

    /**
     * {@inheritdoc}
     */
    private function methodName(): string
    {
        return 'satisfy';
    }
}
