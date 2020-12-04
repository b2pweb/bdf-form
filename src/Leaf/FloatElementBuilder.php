<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\DataTransformerAdapter;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

/**
 * Builder for a float element
 *
 * @see FloatElement
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
     * @var int
     */
    private $roundingMode = NumberToLocalizedStringTransformer::ROUND_DOWN;


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
     * @param int $mode One of the NumberToLocalizedStringTransformer::ROUND_ constant
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
        return new FloatElement($validator, $transformer);
    }

    /**
     * {@inheritdoc}
     */
    protected function numberTransformer(): TransformerInterface
    {
        return new DataTransformerAdapter(new class($this->scale, $this->grouping, $this->roundingMode) extends NumberToLocalizedStringTransformer {
            public function reverseTransform($value)
            {
                return parent::reverseTransform(is_scalar($value) || $value === null ? (string) $value : $value);
            }
        });
    }
}
