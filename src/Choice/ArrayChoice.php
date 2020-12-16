<?php

namespace Bdf\Form\Choice;

/**
 * Implementation of choice using an array
 */
final class ArrayChoice implements ChoiceInterface
{
    /**
     * The list of choices
     *
     * Key: should be the label of the choice
     * Value: the value
     *
     * @var array
     */
    private $choices;

    /**
     * ArrayChoice constructor.
     *
     * @param array $choices The choices. To declare label, use associative array with key as label
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
    public function view(?callable $configurator = null): array
    {
        $view = [];

        foreach ($this->choices as $label => $value) {
            $view[] = $choice = new ChoiceView($value, $label);

            if ($configurator) {
                $configurator($choice);
            }
        }

        return $view;
    }
}
