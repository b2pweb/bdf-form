<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Symfony\Component\Validator\Constraint;

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
    final public function satisfy(Constraint|callable $constraint, ?string $message = null, bool $append = true)
    {
        $this->getElementBuilder()->satisfy($constraint, $message, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function transformer(callable|TransformerInterface $transformer, bool $append = true)
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
