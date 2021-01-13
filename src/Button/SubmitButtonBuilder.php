<?php

namespace Bdf\Form\Button;

use Bdf\Form\Aggregate\FormBuilderInterface;

/**
 * Builder for a submit button
 *
 * <code>
 * $builder
 *     ->submit('btn')
 *     ->value('create')
 *     ->groups(['creation'])
 * ;
 * </code>
 *
 * @see SubmitButton
 * @see FormBuilderInterface::submit()
 */
final class SubmitButtonBuilder implements ButtonBuilderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var class-string<ButtonInterface>
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
     * @param class-string<ButtonInterface> $buttonClass
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
