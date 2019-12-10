<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;

/**
 * Aggregation of transformers
 *
 * - The transformers are applied in order for transform from PHP to HTTP value
 * - For transform from HTTP to PHP, the transformers are applied in reverse order
 */
final class TransformerAggregate implements TransformerInterface
{
    /**
     * @var TransformerInterface[]
     */
    private $transformers;


    /**
     * DataTransformerChain constructor.
     *
     * @param TransformerInterface[] $transformers
     */
    public function __construct(array $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * {@inheritdoc}
     */
    public function transformToHttp($value, ElementInterface $input)
    {
        foreach ($this->transformers as $transformer) {
            $value = $transformer->transformToHttp($value, $input);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function transformFromHttp($value, ElementInterface $input)
    {
        for ($i = count($this->transformers) - 1; $i >= 0; --$i) {
            $value = $this->transformers[$i]->transformFromHttp($value, $input);
        }

        return $value;
    }

    /**
     * Add a transformer at the head of the transformer list
     *
     * @param TransformerInterface $transformer
     */
    public function prepend(TransformerInterface $transformer): void
    {
        array_unshift($this->transformers, $transformer);
    }

    /**
     * Add a transformer at the end of the transformer list
     *
     * @param TransformerInterface $transformer
     */
    public function append(TransformerInterface $transformer): void
    {
        $this->transformers[] = $transformer;
    }
}
