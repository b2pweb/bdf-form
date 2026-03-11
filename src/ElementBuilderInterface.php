<?php

namespace Bdf\Form;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Constraint\Closure;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Builder for instantiate a form element
 *
 * @template-covariant E as ElementInterface
 */
interface ElementBuilderInterface
{
    /**
     * Add a constraint to the current element
     * The constraint are executed after HTTP value transformation, so, it validate the PHP value
     *
     * Prototypes:
     *   function satisfy(Constraint $constraint, null, bool $append = true) - Add a constraint object. The 2nd parameter is ignored
     *   function satisfy(callable $inlineConstraint, ?string $message = null, bool $append = true) - Create a new constraint using a callback. The 2nd parameter is the error message
     *
     * Usage:
     * <code>
     * $builder->satisfy(new MyConstraint()); // Add a constraint
     * $builder->satisfy(new MyConstraint(), null, false); // Prepend the constraint (it will be validated first)
     *
     * // Register a custom constraint
     * // Take the value as first parameter, and the input element as second
     * // It should return the error string if the value is invalid
     * $builder->satisfy(function ($value, ElementInterface $input) {
     *     if (!is_valid($value)) {
     *         return 'my error';
     *     }
     * });
     *
     * // The custom constraint can also handle error code when returns an array
     * $builder->satisfy(function ($value, ElementInterface $input) {
     *     if (!is_valid($value)) {
     *         return ['message' => 'my error', 'code' => 'MY_ERROR'];
     *     }
     * });
     *
     * // You can also return a simple boolean
     * $builder->satisfy(function ($value, ElementInterface $input) {
     *     return is_valid($value);
     * }, 'my error');
     *
     * // Use method reference also works, but the method must be public
     * $builder->satisfy([$this, 'checkElement']);
     * </code>
     *
     * @param Constraint|callable $constraint The constraint
     * @param string|null $message The error message if the first parameter is a callable.
     * @param bool $append Append the validator. Prepend if false
     *
     * @return $this
     *
     * @see RegistryInterface::constraint() For make the constraint
     * @see Closure When use callback as first parameter
     */
    public function satisfy(Constraint|callable $constraint, ?string $message = null, bool $append = true): static;

    /**
     * Add a view transformer
     * The view transformer as the responsability of transform the HTTP value to usable PHP value (used by constraints), and vice-versa
     *
     * When transform to PHP, the transformers are executed in reverse order (last registered is the first executed),
     * and there are called in order for transform to HTTP (last registered is the last executed).
     * The value parameter of each transformer is the previous transformer result
     *
     * <code>
     * $builder->transformer(new MyTransformer()); // Add a transformer (will be executed before previous ones on submit)
     * $builder->transformer(new MyTransformer(), false); // Prepend a transformer (will be executed after previous ones on submit)
     *
     * // Register a custom transformer
     * // The first parameter is the value to transform
     * // The second is the current element
     * // The third is a flag : if true, the transformation is from HTTP to PHP, if false, it's from PHP to HTTP
     * $builder->transformer(function ($value, ElementInterface $input, bool $toPhp) {
     *     if ($toPhp) {
     *         return new Entity($value);
     *     } else {
     *         return $value->export();
     *     }
     * });
     * </code>
     *
     * @param callable|TransformerInterface $transformer The transformer.
     * @param bool $append Append the transformer. Prepend if false
     *
     * @return $this
     *
     * @see TransformerInterface
     */
    public function transformer(callable|TransformerInterface $transformer, bool $append = true): static;

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
    public function value(mixed $value): static;

    /**
     * Build the element
     *
     * @return E
     */
    public function buildElement(): ElementInterface;
}
