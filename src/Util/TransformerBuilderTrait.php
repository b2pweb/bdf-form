<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerAggregate;
use Bdf\Form\Transformer\TransformerInterface;

/**
 * Trait for implements builder of transformer
 */
trait TransformerBuilderTrait
{
    /**
     * @var array
     */
    private $transformers = [];

    /**
     * @var callable[]
     */
    private $transformerProviders = [];

    /**
     * {@inheritdoc}
     *
     * @see ElementBuilderInterface::transformer()
     */
    final public function transformer($transformer, bool $append = true)
    {
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
     * @param callable(RegistryInterface):TransformerInterface[] $provider
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
            $providedTransformers = array_merge($providedTransformers, $provider($this->registry()));
        }

        $transformers = array_map([$this->registry(), 'transformer'], $this->transformers);

        if (!empty($providedTransformers)) {
            $transformers = array_merge($providedTransformers, $transformers);
        }

        switch (count($transformers)) {
            case 0:
                return NullTransformer::instance();

            case 1:
                return $transformers[0];

            default:
                return new TransformerAggregate($transformers);
        }
    }
}
