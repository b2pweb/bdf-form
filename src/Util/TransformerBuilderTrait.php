<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerAggregate;
use Bdf\Form\Transformer\TransformerInterface;

use function array_unshift;
use function count;
use function is_callable;

/**
 * Trait for implements builder of transformer
 */
trait TransformerBuilderTrait
{
    /**
     * @var list<TransformerInterface>
     */
    private array $transformers = [];

    /**
     * @var array<callable(RegistryInterface):(TransformerInterface[])>
     */
    private array $transformerProviders = [];

    /**
     * {@inheritdoc}
     *
     * @see ElementBuilderInterface::transformer()
     */
    final public function transformer(callable|TransformerInterface $transformer, bool $append = true): static
    {
        if (is_callable($transformer)) {
            $transformer = new ClosureTransformer($transformer);
        }

        if ($append === true) {
            $this->transformers[] = $transformer;
        } else {
            array_unshift($this->transformers, $transformer);
        }

        return $this;
    }

    /**
     * Add a new transformer provider
     * The transformer provider permit to create a transformer during the build of the element transformer
     * So the transformer can be configured by the element builder
     *
     * Usage:
     * <code>
     * $this->addTransformerProvider(function (RegistryInterface $registry) {
     *     if ($this->enableTransformer) {
     *         return [new MyTransformer($this->transformerOptions)];
     *     }
     *
     *     return [];
     * });
     * </code>
     *
     * @param callable(RegistryInterface):(TransformerInterface[]) $provider
     */
    final protected function addTransformerProvider(callable $provider): void
    {
        $this->transformerProviders[] = $provider;
    }

    /**
     * Get the registry instance
     *
     * @return RegistryInterface
     */
    abstract protected function registry(): RegistryInterface;

    /**
     * Create the transformer for the element
     *
     * @return TransformerInterface
     */
    private function buildTransformer(): TransformerInterface
    {
        $providedTransformers = [];

        foreach ($this->transformerProviders as $provider) {
            $providedTransformers = [...$providedTransformers, ...$provider($this->registry())];
        }

        $transformers = $this->transformers;

        if (!empty($providedTransformers)) {
            $transformers = [...$providedTransformers, ...$transformers];
        }

        return match (count($transformers)) {
            0 => NullTransformer::instance(),
            1 => $transformers[0],
            default => new TransformerAggregate($transformers),
        };
    }
}
