<?php

namespace Bdf\Form\Leaf;

use BadMethodCallException;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Leaf\View\BooleanElementView;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Bdf\Form\View\ElementViewInterface;
use Bdf\Form\View\FieldViewInterface;
use LogicException;

/**
 * Handle a boolean value, like with checkbox input
 * A value is considered as true when a value is present, and equals to the defined value
 *
 * @see BooleanElementBuilder for build the element
 *
 * @method bool value()
 * @extends LeafElement<bool>
 */
class BooleanElement extends LeafElement
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

    /**
     * {@inheritdoc}
     *
     * @return FieldViewInterface
     */
    public function view(?HttpFieldPath $field = null): ElementViewInterface
    {
        return new BooleanElementView(self::class, (string) $field, $this->httpValue(), $this->httpValue, $this->value(), $this->error()->global());
    }
}
