<?php

namespace Bdf\Form\Choice;

use Bdf\Form\ElementBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice as ChoiceConstraint;

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
     * $builder->choices(['foo', 'bar'], ['min' => 2, 'max' => 6]); // With options array
     * </code>
     *
     * @param ChoiceInterface|array|callable $choices  The allowed values in PHP form.
     * @param null|string|array $options  If options is a string it will be considered as constraint message
     *
     * @return $this
     * @see ChoiceConstraint
     */
    final public function choices($choices, $options = null): self
    {
        if (!$choices instanceof ChoiceInterface) {
            $choices = is_array($choices) ? new ArrayChoice($choices) : new LazzyChoice($choices);
        }

        if (is_string($options)) {
            $options = ['message' => $options, 'multipleMessage' => $options];
        }

        $options['callback'] = [$choices, 'values'];

        $this->choices = $choices;

        return $this->satisfy(new ChoiceConstraint($options));
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
