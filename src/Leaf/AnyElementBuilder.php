<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Override;

/**
 * Builder for any element
 *
 * @see AnyElement
 *
 * @extends AbstractElementBuilder<AnyElement>
 */
class AnyElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait;

    #[Override]
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): AnyElement
    {
        return new AnyElement($validator, $transformer, $this->getChoices());
    }
}
