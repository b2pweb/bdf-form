<?php

namespace Bdf\Form\Child;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;

/**
 * Builder type for instantiate a child
 */
interface ChildBuilderInterface
{
    /**
     * Mark this input as required
     *
     * @param mixed $options
     *
     * @return $this
     */
    public function required($options = null);

    /**
     * Define the hydrator for the child
     * The hydrator is used by fill() method to set the element value into the entity
     *
     * @param HydratorInterface $hydrator
     *
     * @return $this
     *
     * @see ChildInterface::fill()
     */
    public function hydrator(HydratorInterface $hydrator);

    /**
     * Define the extractor for the child
     * The extractor is use by import() method to get the element value from the entity
     *
     * @param ExtractorInterface $extractor
     *
     * @return $this
     *
     * @see ChildInterface::import()
     */
    public function extractor(ExtractorInterface $extractor);

    /**
     * Add a filter
     * The filter is the first transformation step. The value should be normalized here (or filtered when return null), before call of view transformers
     * Note: The filter is only called during form binding (from HTTP value to PHP model value)
     *
     * <code>
     * $builder->addFilter(function ($value, FormChildInterface $input) {
     *     return $this->checkValue($value) ? $value : null;
     * });
     * </code>
     *
     * @param mixed $filter
     * @param bool $append Append the filter. Prepend if false
     *
     * @return $this
     *
     * @see FilterInterface
     */
    public function filter($filter, $append = true);

    /**
     * Define the default value
     * Should be normalized (i.e. formatted as HTTP value)
     * The default will be used when submitting form without (or with an empty) value for the current field
     *
     * Note: The default value is not the initial value, and it's only used on submit.
     *       The default value is not set on a fresh built element.
     *
     * @param mixed $default Default value, in HTTP format
     *
     * @return $this
     *
     * @see ElementBuilderInterface::value() For set the initial value
     */
    public function default($default);

    /**
     * Add an input dependency
     *
     * The point of view of a dependency should respect the way view -> normalization
     *
     * @param string[] $inputNames
     *
     * @return $this
     */
    public function depends(string ...$inputNames);

    /**
     * Creates the child instance
     *
     * @return ChildInterface
     */
    public function buildChild(): ChildInterface;
}
