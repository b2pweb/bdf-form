<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Filter\FilterInterface;

/**
 * Add the filter by calling filter()
 *
 * @see FilterInterface
 * @see ChildBuilderInterface::filter()
 *
 * @implements ElementAttributeProcessorInterface<FilterInterface>
 */
final class FilterAttributeProcessor implements ElementAttributeProcessorInterface
{
    use SimpleMethodCallGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return FilterInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->filter($attribute);
    }

    /**
     * {@inheritdoc}
     */
    private function methodName(): string
    {
        return 'filter';
    }
}
