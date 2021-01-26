<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementBuilderInterface;

/**
 * Forward __call() magic method to an inner element builder
 */
trait MagicCallForwarding
{
    /**
     * Forward call to the inner element builder
     *
     * @param string $name
     * @param array $arguments
     *
     * @return $this|mixed
     */
    final public function __call(string $name, array $arguments)
    {
        $builder = $this->getElementBuilder();
        $return = $builder->$name(...$arguments);

        return $return === $builder ? $this : $return;
    }

    /**
     * Get the inner element builder
     *
     * @return ElementBuilderInterface
     */
    abstract protected function getElementBuilder(): ElementBuilderInterface;
}
