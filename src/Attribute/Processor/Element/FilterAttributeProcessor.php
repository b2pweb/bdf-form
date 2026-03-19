<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Filter\FilterInterface;
use Override;

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

    #[Override]
    public function type(): string
    {
        return FilterInterface::class;
    }

    #[Override]
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->filter($attribute);
    }

    #[Override]
    private function methodName(): string
    {
        return 'filter';
    }
}
