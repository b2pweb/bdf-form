<?php

namespace Bdf\Form\Util;

use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use WeakReference;

/**
 * Implements get and set container methods for an element
 */
trait ContainerTrait
{
    /**
     * @var WeakReference<ChildInterface>|null
     */
    private ?WeakReference $container = null;


    /**
     * @see ElementInterface::container()
     */
    final public function container(): ?ChildInterface
    {
        return $this->container?->get();
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
        $element->container = WeakReference::create($container);

        return $element;
    }
}
