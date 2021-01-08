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
 */
interface RootElementInterface extends ElementInterface
{
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
