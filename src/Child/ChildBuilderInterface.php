<?php

namespace Bdf\Form\Child;

use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\Filter\FilterInterface;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Builder type for instantiate a child
 *
 * @template B is ElementBuilderInterface
 * @mixin B
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
     *
     * The filter is the first transformation step. The value should be normalized here (or filtered when return null), before call of view transformers
     * The default value is applied after all filters, so if a filter returns null, the default value will be used.
     *
     * Note: The filter is only called during form binding (from HTTP value to PHP model value)
     *
     * <code>
     * // The first parameter is the http value
     * // The second is the submitted child
     * // The third is the default value of the child
     * $builder->filter(function ($value, ChildInterface $input, $default) {
     *     return $this->checkValue($value) ? $value : null;
     * });
     * </code>
     *
     * @param FilterInterface|callable(mixed,ChildInterface,mixed):mixed $filter
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
     * Add a configurator for child parameters
     * The configure allow to handle wrapper of child builder
     *
     * Usage:
     * <code>
     * $builder->addParametersConfigurator(function (ChildParameters $parameters) {
     *     $parameters->hydrator = $parameters->hydrator ?? $this->createDefaultHydrator(); // Define a default hydrator
     *     $parameters->dependencies[] = 'myOtherField';
     *
     *     // Define a child decorator
     *     $parameters->factories[] = function (ChildParameters $parameters) {
     *         return new MyChildWrapper($parameters->child);
     *     };
     * });
     * </code>
     *
     * @param callable(ChildParameters):void $configurator
     *
     * @return $this
     */
    public function addParametersConfigurator(callable $configurator);

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
     * Add a model transformer
     * The model transformer as the responsability of transform the element value to model PHP value (i.e. fill()'ed value), and vice-versa
     *
     * When transform to PHP, the transformers are executed in reverse order (last registered is the first executed),
     * and there are called in order for transform to HTTP (last registered is the last executed).
     * The value parameter of each transformer is the previous transformer result
     *
     * <code>
     * $builder->modelTransformer(new MyTransformer()); // Add a transformer (will be executed before previous ones on submit)
     * $builder->modelTransformer(new MyTransformer(), false); // Prepend a transformer (will be executed after previous ones on submit)
     *
     * // Register a custom transformer
     * // The first parameter is the value to transform
     * // The second is the current element
     * // The third is a flag : if true, the transformation is from element to model, if false, it's from PHP to HTTP
     * $builder->modelTransformer(function ($value, ElementInterface $input, bool $toPhp) {
     *     if ($toPhp) {
     *         return new Entity($value);
     *     } else {
     *         return $value->export();
     *     }
     * });
     * </code>
     *
     * @param callable|TransformerInterface|DataTransformerInterface $transformer The transformer. Symfony transformer can be used
     * @param bool $append Append the transformer. Prepend if false
     *
     * @return $this
     *
     * @see TransformerInterface
     */
    public function modelTransformer($transformer, bool $append = true);

    /**
     * Creates the child instance
     *
     * @return ChildInterface
     */
    public function buildChild(): ChildInterface;
}
