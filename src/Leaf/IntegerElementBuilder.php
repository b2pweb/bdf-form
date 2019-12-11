<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\ElementInterface;
use Bdf\Form\Transformer\DataTransformerAdapter;
use Bdf\Form\Transformer\NullTransformer;
use Bdf\Form\Transformer\TransformerAggregate;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ValueValidatorInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

/**
 * Builder for an integer element
 *
 * @see IntegerElement
 */
class IntegerElementBuilder extends AbstractElementBuilder
{
    /**
     * @var bool
     */
    private $raw = false;

    /**
     * @var bool
     */
    private $grouping = false;

    /**
     * @var int
     */
    private $roundingMode = IntegerToLocalizedStringTransformer::ROUND_DOWN;


    /**
     * Set the minimal value constraint
     *
     * @param int $min The minimal value (included)
     * @param string|null $message The error message
     *
     * @return $this
     */
    public function min(int $min, ?string $message = null): self
    {
        $options = ['value' => $min];

        if ($message) {
            $options['message'] = $message;
        }

        $this->satisfy(new GreaterThanOrEqual($options));

        return $this;
    }

    /**
     * Set the maximal value constraint
     *
     * @param int $max The maximal value (included)
     * @param string|null $message The error message
     *
     * @return $this
     */
    public function max(int $max, ?string $message = null): self
    {
        $options = ['value' => $max];

        if ($message) {
            $options['message'] = $message;
        }

        $this->satisfy(new LessThanOrEqual($options));

        return $this;
    }

    /**
     * Enable raw integer mode
     *
     * In raw mode, the value will not be parsed pending of the locale, but only cast the value to int
     * This mode is useful when the input format is normalized, like in APIs
     *
     * @param bool $flag Enable or disable the raw mode
     *
     * @return $this
     */
    public function raw(bool $flag = true): self
    {
        $this->raw = $flag;

        return $this;
    }

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
        if (!$this->raw) {
            $transformer = $this->addNumberTransformer($transformer);
        }

        return new IntegerElement($validator, $transformer);
    }

    protected function addNumberTransformer(TransformerInterface $previous): TransformerInterface
    {
        // Handle null and scalar types
        $numberTransformer = new DataTransformerAdapter(new class($this->grouping, $this->roundingMode) extends IntegerToLocalizedStringTransformer {
            public function reverseTransform($value)
            {
                return parent::reverseTransform(is_scalar($value) || $value === null ? (string) $value : $value);
            }
        });

        if ($previous instanceof NullTransformer) {
            return $numberTransformer;
        }

        if ($previous instanceof TransformerAggregate) {
            $previous->prepend($numberTransformer);

            return $previous;
        }

        return new TransformerAggregate([$numberTransformer, $previous]);
    }
}
