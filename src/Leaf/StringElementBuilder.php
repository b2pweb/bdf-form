<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Builder for string element
 *
 * <code>
 * $builder->string('username')
 *     ->required()
 *     ->length(['min' => 6, 'max' => 32])
 *     ->regex('/[a-z_-]+/i')
 * ;
 * </code>
 *
 * @see StringElement
 * @see FormBuilderInterface::string()
 *
 * @extends AbstractElementBuilder<StringElement>
 */
class StringElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait;

    /**
     * Add a string length constraint
     *
     * Options are keys are : min, max, minMessage, maxMessage
     *
     * Usage:
     * <code>
     * $builder->length(['max' => 256]);
     * </code>
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
     * <code>
     * $builder->regex('/[a-z_-]+/'); // Simple regex
     * $builder->regex(['pattern' => '/[a-z_-]+/', 'message' => 'Invalid value']); // Using options array
     * </code>
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
        return new StringElement($validator, $transformer, $this->getChoices());
    }
}
