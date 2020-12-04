<?php

namespace Bdf\Form\Leaf;

use Bdf\Form\AbstractElementBuilder;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

/**
 * Base builder for an number element
 */
abstract class NumberElementBuilder extends AbstractElementBuilder
{
    /**
     * @var bool
     */
    private $raw = false;


    /**
     * NumberElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(RegistryInterface $registry = null)
    {
        parent::__construct($registry);

        $this->addTransformerProvider([$this, 'provideNumberTransformer']);
    }

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
     * Creates the localized number transformer
     *
     * @return TransformerInterface
     */
    abstract protected function numberTransformer(): TransformerInterface;

    final protected function provideNumberTransformer(): array
    {
        if (!$this->raw) {
            return [$this->numberTransformer()];
        }

        return [];
    }
}
