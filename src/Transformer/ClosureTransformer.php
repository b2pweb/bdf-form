<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Registry\RegistryInterface;
use Override;

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
final readonly class ClosureTransformer implements TransformerInterface
{
    /**
     * @var callable
     */
    private mixed $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    #[Override]
    public function transformToHttp(mixed $value, ElementInterface $input): mixed
    {
        return ($this->callback)($value, $input, false);
    }

    #[Override]
    public function transformFromHttp(mixed $value, ElementInterface $input): mixed
    {
        return ($this->callback)($value, $input, true);
    }
}
