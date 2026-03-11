<?php

namespace Bdf\Form\Choice;

use Override;

/**
 * Implementation of choice using an array
 *
 * @template T
 * @implements ChoiceInterface<T>
 */
final readonly class ArrayChoice implements ChoiceInterface
{
    public function __construct(
        /**
         * The list of choices
         *
         * Key: should be the label of the choice
         * Value: the value
         *
         * @var T[]
         */
        private array $choices,
    ) {}

    #[Override]
    public function values(): array
    {
        return $this->choices;
    }

    #[Override]
    public function view(?callable $configuration = null): array
    {
        $view = [];

        foreach ($this->choices as $label => $value) {
            $view[] = $choice = new ChoiceView($value, $label);

            if ($configuration !== null) {
                $configuration($choice);
            }
        }

        return $view;
    }
}
