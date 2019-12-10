<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Adapter for Symfony data transformer to bdf transformer
 */
final class DataTransformerAdapter implements TransformerInterface
{
    /**
     * The symfony data transformer
     *
     * @var DataTransformerInterface
     */
    private $transformer;


    /**
     * Set the symfony data transformer
     * 
     * @param DataTransformerInterface $transformer
     */
    public function __construct(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }
    
    /**
     * {@inheritdoc}
     */
    public function transformToHttp($value, ElementInterface $input)
    {
        return $this->transformer->transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function transformFromHttp($value, ElementInterface $input)
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
