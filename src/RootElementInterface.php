<?php

namespace Bdf\Form;

use Bdf\Form\Button\ButtonInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Element which represents the root form
 * This element contains all the HTTP fields
 *
 * @see ElementInterface::root() To get the root instance
 *
 * @method void set(string $flag, mixed $value) Define a flag value
 * @method bool is(string $flag) Check if a flag is defined
 *
 * @extends ElementInterface<mixed>
 */
interface RootElementInterface extends ElementInterface
{
    /**
     * Get a button by its name
     *
     * <code>
     * $root = $form->root();
     * $root->submit($request->post());
     *
     * if ($root->button('continue')->clicked()) {
     *     return $this->redirectTo($this->nextPage());
     * }
     * </code>
     *
     * @param non-empty-string $name The button name
     *
     * @throws \OutOfBoundsException When the button is not found
     *
     * @see RootElementInterface::submitButton() To only get the clicked button
     */
    public function button(string $name): ButtonInterface;

    /**
     * The button used to submit the form
     *
     * <code>
     * $root = $form->root();
     * $root->submit($request->post());
     *
     * if ($btn = $root->submitButton()) {
     *     switch ($btn->name()) {
     *         case self::BTN_SAVE:
     *             return $this->doSave($form->value());
     *
     *         case self::BTN_DELETE:
     *             return $this->doDelete($form->value());
     *     }
     * }
     * </code>
     *
     * @return ButtonInterface|null The button, or null if not defined
     *
     * @see RootElementInterface::button() To get a button by its name
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
     * Get the constraint groups related to the button
     *
     * @return string[]
     */
    public function constraintGroups(): array;

    /**
     * Set a flag value
     *
     * @param string $flag Flag name
     * @param bool $value Flag value
     *
     * @return void
     */
    //public function set(string $flag, bool $value): void;

    /**
     * Check a flag value
     * If a flag is not defined, it returns false
     *
     * @param string $flag Flag name
     *
     * @return bool Flag value
     */
    //public function is(string $flag): bool;
}
