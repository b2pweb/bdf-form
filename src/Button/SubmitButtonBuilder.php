<?php

namespace Bdf\Form\Button;

/**
 * Builder for a submit button
 */
final class SubmitButtonBuilder implements ButtonBuilderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $buttonClass;

    /**
     * @var string
     */
    private $value = 'ok';

    /**
     * @var array
     */
    private $groups = [];


    /**
     * SubmitButtonBuilder constructor.
     *
     * @param string $name
     * @param string $buttonClass
     */
    public function __construct(string $name, string $buttonClass = SubmitButton::class)
    {
        $this->name = $name;
        $this->buttonClass = $buttonClass;
    }

    /**
     * {@inheritdoc}
     */
    public function value(string $value): ButtonBuilderInterface
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groups(array $groups): ButtonBuilderInterface
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildButton(): ButtonInterface
    {
        return new $this->buttonClass($this->name, $this->value, $this->groups);
    }
}
