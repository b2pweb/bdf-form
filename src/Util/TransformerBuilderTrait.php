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
     * @see ElementBuilderInterface::transformer()
     */
    final public function transformer($transformer, $append = true)
    {
        if ($append === true) {
            $this->transformers[] = $transformer;
        } else {
            array_unshift($this->transformers, $transformer);
        }

        return $this;
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
        switch (count($this->transformers)) {
            case 0:
                return NullTransformer::instance();

            case 1:
                return $this->registry()->transformer($this->transformers[0]);

            default:
                return new TransformerAggregate(array_map([$this->registry(), 'transformer'], $this->transformers));
        }
    }
}
