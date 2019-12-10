<?php

namespace Bdf\Form;

use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Error\FormError;

/**
 * Base type for the form tree
 */
interface ElementInterface
{
    /**
     * Submit HTTP data to the form element
     * The value will be transformed to PHP value and validated by the element's constraints
     *
     * @param mixed $data The HTTP data. A scalar value for a leaf element, or an array for form / aggregate element.
     *
     * @return $this
     *
     * @see ElementInterface::import() For import PHP value
     * @see ElementInterface::valid() For validates the element after submition
     */
    public function submit($data): self;

    /**
     * Set the PHP value of the element
     *
     * On an aggregate / form element, will import entity on each children elements
     * Once imported, the element value must be attached to the element : `$element->import($value) === $value` must be true
     *
     * @param mixed $entity
     *
     * @return $this
     *
     * @see ElementInterface::submit() For import HTTP data
     */
    public function import($entity): self;

    /**
     * Get the element's value
     * The returned value is the transformed one, and not the raw HTTP value
     * In case of form element without attached entity, a new entity is generates and returned
     *
     * @return mixed
     *
     * @see ElementInterface::httpValue() For get the raw HTTP value
     */
    public function value();

    /**
     * Get the raw HTTP value
     * This value should be equals with submit()'ed data : `$element->submit($value) == $value` should be true
     *
     * Note: The value is generated from the PHP value, which may add some transformations of the original HTTP data
     *
     * @return mixed
     *
     * @see ElementInterface::value() For get the PHP value
     */
    public function httpValue();

    /**
     * Validates the element value
     * On aggregate element, if at least on child is invalid, this method will return false
     *
     * Note: A non-submit()'ed element will return false
     *
     * @return bool
     */
    public function valid(): bool;

    /**
     * Get the errors related to the element
     *
     * If the element is valid (i.e. has no errors), a null object is returned
     * The return value may contains the errors of the element or of its children
     *
     * @return FormError
     */
    public function error(): FormError;

    /**
     * Get the element's container
     *
     * The root element has no container, and return null
     *
     * The container is the element's adapter to ChildInterface, keeping a circular reference
     * `$element->container()->element() === $element` is always true
     *
     * @return ChildInterface|null
     *
     * @see ElementInterface::setContainer() For define the container
     * @see ChildInterface::element() For get the element from the container
     */
    public function container(): ?ChildInterface;

    /**
     * Define the element's container
     * A new instance is returned without modifying the current one
     *
     * @param ChildInterface $container
     *
     * @return static The new element's instance, linked with the container
     *
     * @see ElementInterface::container() For get the container
     */
    public function setContainer(ChildInterface $container): self;

    /**
     * Get the root element of the form
     *
     * This value is never null : if the element has no parent, it must create a new root, and define it-self as the root element
     * If a container is defined, will return the root of the container's parent (i.e. `$element->container()->parent()->root()`)
     *
     * @return RootElementInterface
     */
    public function root(): RootElementInterface;

    /**
     * Get the view object of the element
     *
     * @todo define behavior
     *
     * @return mixed
     */
    public function view();
}
