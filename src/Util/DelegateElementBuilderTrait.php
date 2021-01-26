<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementInterface;

/**
 * Simple implementation of delegated element builder
 *
 * @psalm-require-implements \Bdf\Form\ElementBuilderInterface
 */
trait DelegateElementBuilderTrait
{
    use MagicCallForwarding;

    /**
     * {@inheritdoc}
     */
    final public function satisfy($constraint, $options = null, bool $append = true)
    {
        $this->getElementBuilder()->satisfy($constraint, $options, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function transformer($transformer, bool $append = true)
    {
        $this->getElementBuilder()->transformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function value($value)
    {
        $this->getElementBuilder()->value($value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function buildElement(): ElementInterface
    {
        return $this->getElementBuilder()->buildElement();
    }
}
