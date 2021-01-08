<?php

namespace Bdf\Form;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Builder for instantiate a form element
 */
interface ElementBuilderInterface
{
    /**
     * Add a constraint to the current element
     * The constraint are executed after HTTP value transformation, so, it validate the PHP value
     *
     * Prototypes:
     *   function satisfy(Constraint $constraint, null, bool $append = true) - Add a constraint object. The 2nd parameter is ignored
     *   function satisfy(string $constraintClassName, ?array $options = null, bool $append = true) - Equivalent to satisfy(new $constraintClassName($options), null, $append)
     *   function satisfy(string $constraintClassName, ?string $errorMessage = null, bool $append = true) - Equivalent to satisfy(new $constraintClassName(['message' => $errorMessage]), null, $append)
     *   function satisfy(callable $inlineConstraint, null, bool $append = true) - Create a new constraint using a callback. The 2nd parameter is ignored
     *
     * Usage:
     * <code>
     * $builder->satisfy(new MyConstraint()); // Add a constraint
     * $builder->satisfy(new MyConstraint(), null, false); // Prepend the constraint (it will be validated first)
     * $builder->satisfy(MyConstraint::class); // Use class name (will be used by the registry to make the constraint)
     * $builder->satisfy(MyConstraint::class, ['foo' => 'bar']); // Same as above, but with options
     * $builder->satisfy(MyConstraint::class, 'my error'); // Same as above, but with the option "message" defined to "my error"
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
     * // Use method reference also works, but the method must be public
     * $builder->satisfy([$this, 'checkElement']);
     * </code>
     *
     * @param Constraint|string|callable $constraint The constraint
     * @param array|string|null $options Constraint options if a class name is given as first parameter. If a string is given, it will be used as message option
     * @param bool $append Append the validator. Prepend if false
     *
     * @return $this
     *
     * @see RegistryInterface::constraint() For make the constraint
     */
    public function satisfy($constraint, $options = null, bool $append = true);

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
     * // The third is a flag t: if true, the transformation is from HTTP to PHP, if false, it's from PHP to HTTP
     * $builder->transformer(function ($value, ElementInterface $input, bool $toPhp) {
     *     if ($toPhp) {
     *         return new Entity($value);
     *     } else {
     *         return $value->export();
     *     }
     * });
     * </code>
     *
     * @param callable|TransformerInterface|DataTransformerInterface $transformer The transformer. Symfony transformer can be used
     * @param bool $append Append the validator. Prepend if false
     *
     * @return $this
     *
     * @see TransformerInterface
     */
    public function transformer($transformer, bool $append = true);

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
