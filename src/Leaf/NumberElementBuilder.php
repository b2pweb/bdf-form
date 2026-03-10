<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Choice\ChoiceBuilderTrait;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\TransformerExceptionConstraint;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Base builder for an number element
 *
 * @template E as \Bdf\Form\ElementInterface
 * @extends AbstractElementBuilder<E>
 */
abstract class NumberElementBuilder extends AbstractElementBuilder
{
    use ChoiceBuilderTrait;

    /**
     * @var bool
     */
    private $raw = false;


    /**
     * NumberElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(?RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addTransformerProvider([$this, 'provideNumberTransformer']);
    }

    /**
     * Set the minimal value constraint
     *
     * @param int|float $min The minimal value (included)
     * @param string|null $message The error message
     *
     * @return $this
     */
    public function min(int|float $min, ?string $message = null): self
    {
        $this->satisfy(new GreaterThanOrEqual(value: $min, message: $message));

        return $this;
    }

    /**
     * Set the maximal value constraint
     *
     * @param int|float $max The maximal value (included)
     * @param string|null $message The error message
     *
     * @return $this
     */
    public function max(int|float $max, ?string $message = null): self
    {
        $this->satisfy(new LessThanOrEqual(value: $max, message: $message));

        return $this;
    }

    /**
     * The number must be positive
     *
     * @param string|null $message The error message
     *
     * @return $this
     * @see Positive
     */
    public function positive(?string $message = null): self
    {
        return $this->satisfy(new Positive(message: $message));
    }

    /**
     * Enable raw number mode
     *
     * In raw mode, the value will not be parsed according the the locale, but only cast the value to the php number type
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
     * {@inheritdoc}
     */
    protected function defaultTransformerExceptionConstraint(): TransformerExceptionConstraint
    {
        return new TransformerExceptionConstraint(
            message: 'The value is not a valid number.',
            code: 'INVALID_NUMBER_ERROR',
        );
    }

    /**
     * Creates the localized number transformer
     *
     * @return TransformerInterface
     */
    abstract protected function numberTransformer(): TransformerInterface;

    final protected function provideNumberTransformer(RegistryInterface $registry): array
    {
        if (!$this->raw) {
            return [$this->numberTransformer()];
        }

        return [];
    }
}
