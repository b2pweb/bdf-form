<?php

namespace Bdf\Form\Button;

/**
 * Base type for form buttons
 */
interface ButtonInterface
{
    /**
     * Get the button name
     * Used as identifier : it should be unique over all the form
     *
     * @return string
     */
    public function name(): string;

    /**
     * Check if the button is clicked
     * It should have only one clicked button per form
     *
     * @return bool
     */
    public function clicked(): bool;

    /**
     * Submit the form data on the button to check if it has been clicked
     *
     * @param mixed $data The form data
     *
     * @return bool true is the button is clicked, or false
     */
    public function submit($data): bool;

    /**
     * Get the constraint groups related the the button
     *
     * @return string[]
     */
    public function constraintGroups(): array;
}
