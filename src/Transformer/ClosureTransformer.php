<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Registry\RegistryInterface;

/**
 * Wrap a closure into a Transformer
 *
 * <code>
 * new ClosureTransformer(function ($value, ElementInterface $element, bool $toPhp) {
 *     return $toPhp ? parse($value) : normalize($value);
 * });
 * </code>
 *
 * @see RegistryInterface::transformer() With callbable should return a ClosureTransformer
 * @see ElementBuilderInterface::transformer() For register a transformer on an element
 */
final class ClosureTransformer implements TransformerInterface
{
    /**
     * @var callable
     */
    private $callback;


    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function transformToHttp($value, ElementInterface $input)
    {
        return ($this->callback)($value, $input, false);
    }

    /**
     * {@inheritdoc}
     */
    public function transformFromHttp($value, ElementInterface $input)
    {
        return ($this->callback)($value, $input, true);
    }
}
