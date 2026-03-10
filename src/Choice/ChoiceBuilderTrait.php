<?php

namespace Bdf\Form\Choice;

use Bdf\Form\ElementBuilderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice as ChoiceConstraint;

use function is_array;

/**
 * Trait for configure choices on an element
 */
trait ChoiceBuilderTrait
{
    /**
     * @var ChoiceInterface|null
     */
    private $choices;

    /**
     * Define choices for the element
     *
     * Note: a constraint will be added, so this method should not be called multiple times
     *
     * Usage:
     * <code>
     * $builder->choices(['foo', 'bar']); // Simple choice, without defined label
     * // With label as key
     * $builder->choices([
     *     'First choice' => 'foo',
     *     'Second choice' => 'bar',
     * ]);
     *
     * // Using lazy loading
     * // Return value must follow array choices syntax
     * $builder->choices(function () {
     *     return $this->repository->loadChoices();
     * });
     *
     * $builder->choices(['foo', 'bar'], 'my error'); // With message
     * $builder->choices(['foo', 'bar'], min: 2, max: 6); // With custom options
     * </code>
     *
     * @param ChoiceInterface|array|callable $choices  The allowed values in PHP form.
     * @param string|null $message The error message.
     *
     * @return $this
     * @see ChoiceConstraint
     */
    final public function choices(ChoiceInterface|array|callable $choices, ?string $message = null, ?bool $multiple = null, ?bool $strict = null, ?int $min = null, ?int $max = null, ?string $minMessage = null, ?string $maxMessage = null): self
    {
        if (!$choices instanceof ChoiceInterface) {
            $choices = is_array($choices) ? new ArrayChoice($choices) : new LazyChoice($choices);
        }

        $callback = $choices->values(...);
        $this->choices = $choices;

        return $this->satisfy(
            new ChoiceConstraint(
                callback: $callback,
                multiple: $multiple,
                strict: $strict,
                min: $min,
                max: $max,
                message: $message,
                multipleMessage: $message,
                minMessage: $minMessage,
                maxMessage: $maxMessage
            )
        );
    }

    /**
     * Get the built choices
     *
     * @return ChoiceInterface|null
     * @internal
     */
    final protected function getChoices(): ?ChoiceInterface
    {
        return $this->choices;
    }

    /**
     * {@inheritdoc}
     *
     * @see ElementBuilderInterface::satisfy()
     */
    abstract public function satisfy(Constraint|callable $constraint, ?string $message = null, bool $append = true);
}
