<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;

/**
 * Define the extractor by calling extract()
 *
 * @see ExtractorInterface
 * @see ChildBuilderInterface::extractor()
 *
 * @implements ElementAttributeProcessorInterface<ExtractorInterface>
 */
final class ExtractorAttributeProcessor implements ElementAttributeProcessorInterface
{
    use SimpleMethodCallGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return ExtractorInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->extractor($attribute);
    }

    /**
     * {@inheritdoc}
     */
    private function methodName(): string
    {
        return 'extractor';
    }
}
