<?php

namespace Bdf\Form\Custom;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;

/**
 * Class CustomFormBuilder
 */
class CustomFormBuilder implements ElementBuilderInterface
{
    /**
     * @var FormBuilder
     *
     * @todo interface
     */
    private $builder;

    /**
     * @var string|callable
     */
    private $formFactory;


    /**
     * CustomFormBuilder constructor.
     *
     * @param string|callable $formFactory
     * @param FormBuilder $builder
     */
    public function __construct($formFactory, ?FormBuilder $builder = null)
    {
        $this->formFactory = $formFactory;
        $this->builder = $builder ?: new FormBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function satisfy($constraint, $options = null, $append = true)
    {
        $this->builder->satisfy($constraint, $options, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function transformer($transformer, $append = true)
    {
        $this->builder->transformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value($value)
    {
        $this->builder->value($value);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return CustomForm
     */
    public function buildElement(): ElementInterface
    {
        if (is_string($this->formFactory)) {
            $className = $this->formFactory;

            return new $className($this->builder);
        }

        return ($this->formFactory)($this->builder);
    }

    /**
     * Forward call to inner builder
     *
     * @param string $name
     * @param array $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $this->builder->$name(...$arguments);

        return $this;
    }
}
