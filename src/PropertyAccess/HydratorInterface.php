<?php

namespace Bdf\Form\PropertyAccess;

/**
 * Accessor for fill the target
 */
interface HydratorInterface extends AccessorInterface
{
    const HYDRATION = 'hydration';

    /**
     * Hydrate the target
     *
     * @param array|object $target
     *
     * @return void
     */
    public function hydrate(&$target);
}
