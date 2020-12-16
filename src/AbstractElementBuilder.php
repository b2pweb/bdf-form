<?php

namespace Bdf\Form;

use Bdf\Form\Registry\Registry;
use Bdf\Form\Registry\RegistryInterface;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Util\TransformerBuilderTrait;
use Bdf\Form\Util\ValidatorBuilderTrait;
use Bdf\Form\Validator\ValueValidatorInterface;

/**
 * Base builder for elements
 */
abstract class AbstractElementBuilder implements ElementBuilderInterface
{
    use TransformerBuilderTrait;
    use ValidatorBuilderTrait;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var mixed
     */
    private $value;


    /**
     * ElementBuilder constructor.
     *
     * @param RegistryInterface|null $registry
     */
    public function __construct(RegistryInterface $registry = null)
    {
        $this->registry = $registry ?: new Registry();
    }

    /**
     * {@inheritdoc}
     */
    final public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function buildElement(): ElementInterface
    {
        $element = $this->createElement($this->buildValidator(), $this->buildTransformer());

        if ($this->value !== null) {
            $element->import($this->value);
        }

        return $element;
    }

    /**
     * Creates the element
     *
     * @param ValueValidatorInterface $validator
     * @param TransformerInterface $transformer
     *
     * @return ElementInterface
     */
    abstract protected function createElement(ValueValidatorInterface $validator, TransformerInterface $transformer): ElementInterface;

    /**
     * {@inheritdoc}
     */
    final protected function registry(): RegistryInterface
    {
        return $this->registry;
    }
}
