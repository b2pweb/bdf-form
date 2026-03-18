<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;

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

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return HydratorInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->hydrator($attribute);
    }

    /**
     * {@inheritdoc}
     */
    private function methodName(): string
    {
        return 'hydrator';
    }
}
