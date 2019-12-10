<?php

namespace Bdf\Form\PropertyAccess;

use Bdf\Form\Child\ChildInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Base type for access to model data
 */
interface AccessorInterface
{
    /**
     * Set property accessor
     * 
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor): void;

    /**
     * Set form element
     * 
     * @param ChildInterface $formElement
     */
    public function setFormElement(ChildInterface $formElement): void;
}
