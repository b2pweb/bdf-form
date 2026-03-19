<?php

namespace Bdf\Form\Attribute\Processor\Element;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Override;

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

    #[Override]
    public function type(): string
    {
        return ExtractorInterface::class;
    }

    #[Override]
    public function process(ChildBuilderInterface $builder, object $attribute): void
    {
        $builder->extractor($attribute);
    }

    #[Override]
    private function methodName(): string
    {
        return 'extractor';
    }
}
