<?php

namespace Bdf\Form\Choice;

/**
 * Implementation of choice using an array
 *
 * @template T
 * @implements ChoiceInterface<T>
 */
final class ArrayChoice implements ChoiceInterface
{
    /**
     * The list of choices
     *
     * Key: should be the label of the choice
     * Value: the value
     *
     * @var T[]
     */
    private $choices;

    /**
     * ArrayChoice constructor.
     *
     * @param T[] $choices The choices. To declare label, use associative array with key as label
     */
    public function __construct(array $choices)
    {
        $this->choices = $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function values(): array
    {
        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function view(?callable $configuration = null): array
    {
        $view = [];

        foreach ($this->choices as $label => $value) {
            $view[] = $choice = new ChoiceView($value, $label);

            if ($configuration) {
                $configuration($choice);
            }
        }

        return $view;
    }
}
