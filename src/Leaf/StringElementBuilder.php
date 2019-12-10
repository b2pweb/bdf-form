<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Builder for string element
 *
 * @see StringElement
 */
class StringElementBuilder extends AbstractElementBuilder
{
    /**
     * Add a string length constraint
     *
     * Options are keys are : min, max, minMessage, maxMessage
     *
     * @param array $options
     *
     * @return $this
     *
     * @see Length For options
     */
    public function length(array $options): self
    {
        return $this->satisfy(new Length($options));
    }

    /**
     * Add a regex constraint
     *
     * @param string|array $options The pattern if string, or the array of options
     *
     * @return $this
     *
     * @see Regex
     */
    public function regex($options): self
    {
        return $this->satisfy(new Regex($options));
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new StringElement($validator, $transformer);
    }
}
