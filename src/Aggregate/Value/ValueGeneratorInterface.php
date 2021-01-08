<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\Aggregate\FormInterface;
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
     * The attached will replace the previous one
     *
     * @param mixed $entity
     * @see FormInterface::attach()
     */
    public function attach($entity): void;

    /**
     * Generate the value
     * This method should be stateless : calling this method multiple times with same argument should return the same value
     *
     * @param ElementInterface $element The source element
     *
     * @return mixed
     * @see FormInterface::value()
     */
    public function generate(ElementInterface $element);
}
