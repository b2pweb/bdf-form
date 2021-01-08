<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use InvalidArgumentException;

/**
 * Builder for a boolean element
 *
 * Note: `default()` method should not be used, because a "false" value is an absent value, which will use the default value, and force the value to "true"
 *       To define a default value for the view, use `value()` instead
 *
 * <code>
 * $builder->boolean('enable')->value(true);
 * </code>
 *
 * @see BooleanElement
 * @see FormBuilderInterface::boolean()
 */
class BooleanElementBuilder extends AbstractElementBuilder
{
    /**
     * @var string
     */
    private $httpValue = '1';

    /**
     * Define the HTTP value used for represent the true value
     *
     * @param string $httpValue A non-empty string value. The default value is "1"
     *
     * @return $this
     *
     * @see ElementInterface::httpValue()
     */
    public function httpValue(string $httpValue): self
    {
        if (empty($httpValue)) {
            throw new InvalidArgumentException('The httpValue must be a non-empty string');
        }

        $this->httpValue = $httpValue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new BooleanElement($validator, $transformer, $this->httpValue);
    }
}
