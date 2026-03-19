<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Override;

/**
 * Define as hydrator by calling hydrator()
 *
 * @see ChildBuilderInterface::hydrator()
 * @see HydratorInterface
 *
 * @implements ElementAttributeProcessorInterface<HydratorInterface>
 */
final class HydratorAttributeProcessor implements ElementAttributeProcessorInterface
{
    use SimpleMethodCallGeneratorTrait;

    #[Override]
    public function type(): string
    {
        return HydratorInterface::class;
    }

    #[Override]
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->hydrator($attribute);
    }

    #[Override]
    private function methodName(): string
    {
        return 'hydrator';
    }
}
