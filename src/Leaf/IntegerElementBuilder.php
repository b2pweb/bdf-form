<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\DataTransformerAdapter;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;

/**
 * Builder for an integer element
 *
 * <code>
 * $builder->integer('id')
 *     ->required()
 *     ->min(15)
 *     ->raw()
 * ;
 * </code>
 *
 * @see IntegerElement
 * @see FormBuilderInterface::integer()
 *
 * @extends NumberElementBuilder<IntegerElement>
 */
class IntegerElementBuilder extends NumberElementBuilder
{
    /**
     * @var bool
     */
    private $grouping = false;

    /**
     * @var int
     */
    private $roundingMode = IntegerToLocalizedStringTransformer::ROUND_DOWN;


    /**
     * Enable grouping of thousands
     *
     * Note: The element must not in raw() mode to works
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
     * Note: The element must not in raw() mode to works
     *
     * @param int $mode One of the IntegerToLocalizedStringTransformer::ROUND_ constant
     *
     * @return $this
     */
    public function roundingMode(int $mode): self
    {
        $this->roundingMode = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface
    {
        return new IntegerElement($validator, $transformer, $this->getChoices());
    }

    /**
     * {@inheritdoc}
     */
    protected function numberTransformer(): TransformerInterface
    {
        // Handle null and scalar types
        return new DataTransformerAdapter(new class($this->grouping, $this->roundingMode) extends IntegerToLocalizedStringTransformer {
            public function reverseTransform($value)
            {
                return parent::reverseTransform(is_scalar($value) || $value === null ? (string) $value : $value);
            }
        });
    }
}
