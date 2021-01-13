<?php

namespace Bdf\Form\Util;

use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;

/**
 * Implements get and set container methods for an element
 */
trait ContainerTrait
{
    /**
     * @var ChildInterface|null
     */
    private $container;


    /**
     * @see ElementInterface::container()
     */
    final public function container(): ?ChildInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     * @see ElementInterface::setContainer()
     */
    final public function setContainer(ChildInterface $container): ElementInterface
    {
        $element = clone $this;
        $element->container = $container;

        return $element;
    }
}
