<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use Override;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Adapter for Symfony data transformer to bdf transformer
 */
final readonly class DataTransformerAdapter implements TransformerInterface
{
    public function __construct(
        /**
         * The symfony data transformer
         */
        private DataTransformerInterface $transformer,
    ) {}

    #[Override]
    public function transformToHttp(mixed $value, ElementInterface $input): mixed
    {
        return $this->transformer->transform($value);
    }

    #[Override]
    public function transformFromHttp(mixed $value, ElementInterface $input): mixed
    {
        return $this->transformer->reverseTransform($value);
    }

    /**
     * Get the symfony transformer
     *
     * @return DataTransformerInterface
     */
    public function getTransformer(): DataTransformerInterface
    {
        return $this->transformer;
    }
}
