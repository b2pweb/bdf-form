<?php

namespace Bdf\Form\Child;

use Bdf\Form\Aggregate\ChildAggregateInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use Bdf\Form\Error\FormError;
use Bdf\Form\PropertyAccess\AccessorInterface;
use Bdf\Form\View\ElementViewInterface;

/**
 * Wrapper for a form sub-element
 * A child always has a parent, which is a an instance of ChildAggregateInterface
 *
 * A child handle :
 * - The HTTP field extraction
 * - Applying filters
 * - Hydrator and extractor
 * - Dependencies and form tree hierarchy
 */
interface ChildInterface
{
    /**
     * Get the related element
     *
     * Note: `$child->element()->container() === $child` is always true
     *
     * @return ElementInterface
     *
     * @see ElementInterface::container() For get the child from the element
     */
    public function element(): ElementInterface;

    /**
     * The parent element
     *
     * @return ChildAggregateInterface
     */
    public function parent(): ChildAggregateInterface;

    /**
     * Define a new parent
     * If a parent is already set, a new instance will be created
     *
     * Note: The child variable must be reassigned with the `setParent()` return value
     *
     * @param ChildAggregateInterface $parent
     *
     * @return static The child instance linked with the parent
     */
    public function setParent(ChildAggregateInterface $parent): self;

    /**
     * Get the element's name
     *
     * The name is only used internally for the form elements tree
     *
     * The HTTP field name is handled by submit() and httpFields()
     * The PHP field name is handled by import() and fill()
     *
     * The expression `$child->parent()[$child->name()] === $child` must be true
     *
     * @return string
     */
    public function name(): string;

    /**
     * List of sibling input dependencies
     * Dependencies must be submitted before current child
     * The returned list contains the same keys and values
     *
     * @return string[]
     */
    public function dependencies(): array;

    /**
     * Import values from the entity
     * Use a PropertyAccessor for extract related attributes
     *
     * @param mixed $entity
     *
     * @see AccessorInterface
     */
    public function import($entity): void;

    /**
     * Hydrate entity with the child value
     * Use a PropertyAccessor for hydrate related attributes
     * This is the opposite operation of import()
     *
     * @param mixed $entity
     */
    public function fill(&$entity): void;

    /**
     * Submit HTTP form data
     *
     * The data contains all form data, and it's the responsibility of the child to filter the required fields
     * (Implementation can be : `$this->element()->submit($data[$this->name()]);`)
     *
     * In case of embedded aggregate / form, the child implementation can choose if http fields are flatten, or as sub-array.
     *
     * @param mixed $data
     *
     * @return bool true on success (data are valid), or false if there is a form error
     *
     * @see ElementInterface::submit() The element submit() method
     */
    public function submit($data): bool;

    /**
     * Submit HTTP form data without override previous ones
     * Unlike `ChildInterface::submit()` default value will not be used if the http value is not present, but will keep the last one.
     *
     * @param mixed $data The HTTP fields
     *
     * @return bool true on success (data are valid), or false if there is a form error
     *
     * @see ElementInterface::patch() The element patch() method
     */
    public function patch($data): bool;

    /**
     * Export the http value of the child as an array
     * The keys are the http field name and the value, the httpValue() of the element
     *
     * @return array
     */
    public function httpFields(): array;

    /**
     * The child's errors
     * Behave same as ElementInterface::error()
     *
     * @return FormError
     *
     * @see ElementInterface::error()
     */
    public function error(): FormError;

    /**
     * Get the view for the given child
     *
     * @param HttpFieldPath|null $field The parent field name. The child should append it's HTTP field to generate the real element view
     *
     * @return ElementViewInterface
     * @see ElementInterface::view() For generate the element view
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface;
}
