<?php

namespace Bdf\Form\Choice;

/**
 * Choice container
 * Store possible values for an element
 */
interface ChoiceInterface
{
    /**
     * Get the available PHP values
     *
     * @return array
     */
    public function values(): array;

    /**
     * Get the available values for view display
     *
     * @param callable(ChoiceView):void|null $configuration The view configuration callback
     *
     * @return ChoiceView[]
     */
    public function view(?callable $configuration = null): array;
}
