<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\ElementInterface;

/**
 * Generate generator for a form value
 *
 * @see ElementInterface::value()
 */
interface ValueGeneratorInterface
{
    /**
     * Attach the entity to use by the generator
     *
     * @param mixed $entity
     */
    public function attach($entity): void;

    /**
     * Generate the value
     *
     * @param ElementInterface $element
     *
     * @return mixed
     */
    public function generate(ElementInterface $element);
}
