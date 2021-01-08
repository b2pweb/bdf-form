<?php

namespace Bdf\Form\Child;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;

/**
 * Builder type for instantiate a child
 */
interface ChildBuilderInterface
{
    /**
     * Define the hydrator for the child
     * The hydrator is used by fill() method to set the element value into the entity
     *
     * @param HydratorInterface $hydrator
     *
     * @return $this
     *
     * @see ChildInterface::fill()
     * @see ChildBuilder::setter() For define simple hydrator
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
     * @see ChildBuilder::getter() For define simple extractor
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
     * @param FilterInterface|callable $filter
     * @param bool $append Append the filter. Prepend if false
     *
     * @return $this
     *
     * @see FilterInterface
     */
    public function filter($filter, bool $append = true);

    /**
     * Define the default value
     * The value must be the PHP value (i.e. the parsed HTTP value)
     * The default will be used when submitting form without (or with an empty) value for the current field
     *
     * Note: The default value is not the initial value, and it's only used on submit.
     *       The default value is not set on a fresh built element.
     *
     * <code>
     * $builder->dateTime('start')->default(new DateTime()); // Define default value
     * </code>
     *
     * @param mixed $default Default value
     *
     * @return $this
     *
     * @see ElementBuilderInterface::value() For set the initial value
     */
    public function default($default);

    /**
     * Add an input dependency
     *
     * Dependencies are always submit()'ed before the current element
     *
     * Note: This method use field name, and not field path, only dependencies on current form can be defined
     *       Circular references are not handled
     *
     * <code>
     * $builder->string('password');
     * $builder->string('confirmation')->depends('password')->satisfy(function ($value, $input) {
     *     if ($value !== FieldPath::parse('password')->value($input)) {
     *         return 'Invalid confirmation';
     *     }
     * })
     * </code>
     *
     * @param string[] $inputNames The input names
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
