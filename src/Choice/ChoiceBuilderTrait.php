<?php

namespace Bdf\Form\Choice;

use Bdf\Form\ElementBuilderInterface;
use ReflectionClass;
use Symfony\Component\Validator\Constraints\Choice as ChoiceConstraint;

use function is_array;
use function sprintf;
use function trigger_error;

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
     * @param null|string|array $message The error message.
     *
     * @return $this
     * @see ChoiceConstraint
     */
    final public function choices($choices, $message = null, ?bool $multiple = null, ?bool $strict = null, ?int $min = null, ?int $max = null, ?string $minMessage = null, ?string $maxMessage = null): self
    {
        if (!$choices instanceof ChoiceInterface) {
            $choices = is_array($choices) ? new ArrayChoice($choices) : new LazyChoice($choices);
        }

        if (is_array($message)) {
            @trigger_error(sprintf('Passing an array of options on %s is deprecated since 1.7 and will be removed on 2.0, use named arguments instead.', __METHOD__), E_USER_DEPRECATED);

            $multiple = $multiple ?? $message['multiple'] ?? null;
            $strict = $strict ?? $message['strict'] ?? null;
            $min = $min ?? $message['min'] ?? null;
            $max = $max ?? $message['max'] ?? null;
            $minMessage = $minMessage ?? $message['minMessage'] ?? null;
            $maxMessage = $maxMessage ?? $message['maxMessage'] ?? null;
            $message = $message['message'] ?? null;
        }

        static $isSf4 = null;

        if ($isSf4 === null) {
            /** @psalm-suppress PossiblyNullReference */
            $isSf4 = (new ReflectionClass(ChoiceConstraint::class))->getConstructor()->getNumberOfParameters() === 1;
        }

        $callback = [$choices, 'values'];

        $this->choices = $choices;

        return $this->satisfy($isSf4
            ? new ChoiceConstraint(['callback' => $callback, 'message' => $message, 'multipleMessage' => $message, 'multiple' => $multiple, 'strict' => $strict, 'min' => $min, 'max' => $max, 'minMessage' => $minMessage, 'maxMessage' => $maxMessage])
            : new ChoiceConstraint([], null, $callback, $multiple, $strict, $min, $max, $message, $message, $minMessage, $maxMessage)
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
    abstract public function satisfy($constraint, $options = null, bool $append = true);
}
