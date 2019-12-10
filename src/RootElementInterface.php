<?php

namespace Bdf\Form;

use Bdf\Form\Button\ButtonInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Element which represents the root form
 * This element contains all the HTTP fields
 */
interface RootElementInterface extends ElementInterface
{
    /**
     * The button used for submit the form
     *
     * @return ButtonInterface|null The button, or null if not defined
     */
    public function submitButton(): ?ButtonInterface;

    /**
     * Get the Symfony validator
     *
     * @return ValidatorInterface
     * @internal
     */
    public function getValidator(): ValidatorInterface;

    /**
     * Get the Symfony property accessor
     *
     * @return PropertyAccessorInterface
     * @internal
     */
    public function getPropertyAccessor(): PropertyAccessorInterface;

    /**
     * Get the constraint groups related the the button
     *
     * @return string[]
     */
    public function constraintGroups(): array;
}
