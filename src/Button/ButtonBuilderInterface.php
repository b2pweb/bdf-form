<?php

namespace Bdf\Form\Button;

/**
 * Builder for a button
 */
interface ButtonBuilderInterface
{
    /**
     * Define the submit button value
     * The value is used to check the click state of the button
     *
     * @param string $value
     *
     * @return $this
     */
    public function value(string $value): ButtonBuilderInterface;

    /**
     * Define the constraint groups to use when the button is clicked
     *
     * @param array $groups List of validation groups
     *
     * @return $this
     *
     * @see https://symfony.com/doc/current/validation/groups.html
     */
    public function groups(array $groups): ButtonBuilderInterface;

    /**
     * Build the button element
     */
    public function buildButton(): ButtonInterface;
}
