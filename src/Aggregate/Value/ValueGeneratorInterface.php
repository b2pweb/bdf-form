<?php

namespace Bdf\Form\Aggregate\Value;

use Bdf\Form\Aggregate\FormInterface;
use Bdf\Form\ElementInterface;

/**
 * Generate generator for a form value
 *
 * @see ElementInterface::value()
 *
 * @template T as object|array
 */
interface ValueGeneratorInterface
{
    /**
     * Attach the entity to use by the generator
     * The attached will replace the previous one
     *
     * If the attached value is an object, generate() should return this object
     *
     * @param T $entity
     * @see FormInterface::attach()
     */
    public function attach(mixed $entity): void;

    /**
     * Generate the value
     * This method should be stateless : calling this method multiple times with same argument should return the same value
     *
     * If the value needs finalization, this method may return a temporary value used as builder (for example, an array of constructor arguments),
     * and the finalization will be done by the form after all generators are called.
     *
     * The returned value must be compatible with hydrators to allows filling the properties, and must be mutable
     *
     * @param ElementInterface $element The source element
     *
     * @return object|array
     * @see FormInterface::value()
     */
    public function generate(ElementInterface $element): object|array;

    /**
     * Finalize the value generation
     *
     * @param object|array $value The value generated using {@see ValueGeneratorInterface::generate()} and filled by hydrators
     * @return T
     */
    public function finalize(object|array $value): object|array;
}
