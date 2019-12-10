<?php

namespace Bdf\Form;

use Bdf\Form\Transformer\TransformerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Factory for create a form element
 *
 * @see AbstractElementBuilder::buildElement()
 */
interface ElementCreationStrategyInterface
{
    /**
     * Creates the element
     *
     * @param Constraint[] $constraints
     * @param TransformerInterface[] $viewTransformers
     *
     * @return ElementInterface
     */
    public function __invoke(array $constraints = [], array $viewTransformers = []): ElementInterface;
}
