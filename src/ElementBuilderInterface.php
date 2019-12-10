<?php

namespace Bdf\Form;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Symfony\Component\Form\DataTransformerInterface as BaseDataTransformerInterface;

/**
 * Builder for instantiate a form element
 */
interface ElementBuilderInterface
{
    /**
     * Add a constraint
     *
     * @param mixed $constraint
     * @param mixed $options
     * @param bool $append Append the validator. Prepend if false
     *
     * @return $this
     */
    public function satisfy($constraint, $options = null, $append = true);

    /**
     * Add a view transformer
     * The view transformer as the responsability of transform the HTTP value to usable PHP value (used by constraints), and vice-versa
     *
     * <code>
     * $builder->transformer(function ($value, FormChildInterface $input, $toPhp) {
     *     if ($toPhp) {
     *         return new Entity($value);
     *     } else {
     *         return $value->export();
     *     }
     * });
     * </code>
     *
     * @param callable|TransformerInterface|BaseDataTransformerInterface $transformer
     * @param bool $append Append the validator. Prepend if false
     *
     * @return $this
     *
     * @see TransformerInterface
     */
    public function transformer($transformer, $append = true);

    /**
     * Define the initial value of the element
     * Setting the value is equivalent with calling `ElementInterface::import()` after building the element
     *
     * Note: The initial value is not the default value : it will be overridden by submit() or import()
     *       while default value is used when submitting an empty value
     *
     * @param mixed $value The value, in PHP
     *
     * @return $this
     *
     * @see ChildBuilderInterface::default() For setting the default value
     */
    public function value($value);

    /**
     * Build the element
     *
     * @return ElementInterface
     */
    public function buildElement(): ElementInterface;
}
