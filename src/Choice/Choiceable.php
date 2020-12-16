<?php

namespace Bdf\Form\Choice;

/**
 * Base type for element which contains a choice of values
 */
interface Choiceable
{
    /**
     * Get available choices for the element
     * Returns null if the element has no configured choices
     *
     * @return ChoiceInterface|null
     */
    public function choices(): ?ChoiceInterface;
}
