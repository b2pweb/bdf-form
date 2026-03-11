<?php

namespace Bdf\Form\PropertyAccess;

/**
 * Accessor for fill the target
 */
interface HydratorInterface extends AccessorInterface
{
    public const string HYDRATION = 'hydration';

    /**
     * Hydrate the target
     *
     * @param array|object $target Entity to hydrate
     * @param mixed $value The value to set
     *
     * @return void
     */
    public function hydrate(array|object &$target, mixed $value)/*: void*/;
}
