<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\Transformer\LocalizedNumberTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use NumberFormatter;

/**
 * Builder for a float element
 *
 * <code>
 * $builder->float('weight')
 *     ->required()
 *     ->min(3.5)
 *     ->raw()
 * ;
 * </code>
 *
 * @see FloatElement
 * @see FormBuilderInterface::float()
 *
 * @extends NumberElementBuilder<FloatElement>
 */
class FloatElementBuilder extends NumberElementBuilder
{
    /**
     * @var int|null
     */
    private $scale = null;

    /**
     * @var bool
     */
    private $grouping = false;

    /**
     * @var NumberFormatter::ROUND_*
     */
    private $roundingMode = NumberFormatter::ROUND_DOWN;


    /**
     * Enable grouping of thousands
     *
     * Note: The element must not be in raw() mode to works
     *
     * @param bool $flag Enable or disable grouping
     *
     * @return $this
     */
    public function grouping(bool $flag = true): self
    {
        $this->grouping = $flag;

        return $this;
    }

    /**
     * How to round the decimal values
     *
     * Note: The element must not be in raw() mode to works
     *
     * @param NumberFormatter::ROUND_* $mode One of the NumberFormatter::ROUND_ constant
     *
     * @return $this
     */
    public function roundingMode(int $mode): self
    {
        $this->roundingMode = $mode;

        return $this;
    }

    /**
     * Define the number of digits of the decimal part
     *
     * Note: The element must not be in raw() mode to works
     *
     * @param int $scale
     *
     * @return $this
     */
    public function scale(int $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new FloatElement($validator, $transformer, $this->getChoices());
    }

    /**
     * {@inheritdoc}
     */
    protected function numberTransformer(): TransformerInterface
    {
        return new LocalizedNumberTransformer($this->scale, $this->grouping, $this->roundingMode);
    }
}
