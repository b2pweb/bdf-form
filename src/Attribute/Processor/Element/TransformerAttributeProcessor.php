<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Transformer\TransformerInterface;

/**
 * Add the transformer by calling transformer()
 *
 * @see ElementBuilderInterface::transformer()
 * @see TransformerInterface
 *
 * @implements ElementAttributeProcessorInterface<TransformerInterface>
 */
final class TransformerAttributeProcessor implements ElementAttributeProcessorInterface
{
    use SimpleMethodCallGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return TransformerInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->transformer($attribute);
    }

    /**
     * {@inheritdoc}
     */
    private function methodName(): string
    {
        return 'transformer';
    }
}
