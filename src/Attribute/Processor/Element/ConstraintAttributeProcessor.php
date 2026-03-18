<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Override;
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

    #[Override]
    public function type(): string
    {
        return Constraint::class;
    }

    #[Override]
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->satisfy($attribute);
    }

    #[Override]
    private function methodName(): string
    {
        return 'satisfy';
    }
}
