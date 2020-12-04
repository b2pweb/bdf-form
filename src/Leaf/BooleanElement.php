<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;

/**
 * Handle a boolean value, like with checkbox input
 * A value is considered as true when a value is present
 *
 * @method bool value()
 */
final class BooleanElement extends LeafElement
{
    /**
     * @var string
     */
    private $httpValue = '1';

    /**
     * BooleanElement constructor.
     *
     * @param ValueValidatorInterface|null $validator
     * @param TransformerInterface|null $transformer
     * @param string $httpValue Value to use as "true" value for HTTP value
     */
    public function __construct(?ValueValidatorInterface $validator = null, ?TransformerInterface $transformer = null, string $httpValue = '1')
    {
        parent::__construct($validator, $transformer);

        $this->httpValue = $httpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function toPhp($httpValue)
    {
        return (bool) $httpValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function toHttp($phpValue)
    {
        return $phpValue ? $this->httpValue : null;
    }
}
