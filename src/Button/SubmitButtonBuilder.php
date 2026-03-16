<?php

namespace Bdf\Form\Button;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Override;

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
    private readonly string $name;

    /**
     * @var class-string<ButtonInterface>
     */
    private string $buttonClass;

    /**
     * @var string
     */
    private string $value = 'ok';

    /**
     * @var string[]
     */
    private array $groups = [];


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

    #[Override]
    public function value(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    #[Override]
    public function groups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    #[Override]
    public function buildButton(): ButtonInterface
    {
        return new $this->buttonClass($this->name, $this->value, $this->groups);
    }
}
