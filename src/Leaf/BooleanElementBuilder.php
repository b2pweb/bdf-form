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
 * // Use boolean string for an API which use showRoute=true or showRoute=false
 * $builder->boolean('showRoute')->value(false)->booleanString();
 * </code>
 *
 * @see BooleanElement
 * @see FormBuilderInterface::boolean()
 *
 * @extends AbstractElementBuilder<AbstractBooleanElement>
 */
class BooleanElementBuilder extends AbstractElementBuilder
{
    /**
     * @var string
     */
    private $httpValue = '1';

    /**
     * @var bool
     */
    private $booleanString = false;

    /**
     * Define the HTTP value used for represent the true value
     *
     * Note: This method cannot be used with {@see BooleanElementBuilder::booleanString()} enabled
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
     * Use a boolean string instead of follow standard HTTP boolean behavior
     *
     * A boolean string is considered as true when the value is on of the following: "true", "on", "yes", "1"
     * The value is considered as false when the value is on of the following: "false", "off", "no", "0"
     * For any other value, the value is considered as null
     *
     * Note: Case and space are ignored, so "True", "ON", " yes " are valid values
     *
     * @param bool $flag Enable or disable the boolean string behavior
     *
     * @return $this
     *
     * @see filter_var() with FILTER_VALIDATE_BOOLEAN is used for convert the value
     * @see BooleanStringElement Will be created instead of BooleanElement if this method is called
     */
    public function booleanString(bool $flag = true): self
    {
        $this->booleanString = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return $this->booleanString
            ? new BooleanStringElement($validator, $transformer)
            : new BooleanElement($validator, $transformer, $this->httpValue)
        ;
    }
}
