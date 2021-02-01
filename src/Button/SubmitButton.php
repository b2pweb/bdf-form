<?php

namespace Bdf\Form\Button;

use Bdf\Form\Button\View\ButtonView;
use Bdf\Form\Button\View\ButtonViewInterface;
use Bdf\Form\Child\Http\HttpFieldPath;

/**
 * Simple button implementation
 * The button is considered as clicked when its value is equals to the registered value
 */
final class SubmitButton implements ButtonInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var bool
     */
    private $clicked = false;


    /**
     * SubmitButton constructor.
     *
     * @param string $name
     * @param string $value
     * @param string[] $groups
     */
    public function __construct(string $name, string $value = 'ok', array $groups = [])
    {
        $this->name = $name;
        $this->value = $value;
        $this->groups = $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function clicked(): bool
    {
        return $this->clicked;
    }

    /**
     * {@inheritdoc}
     */
    public function constraintGroups(): array
    {
        return $this->groups;
    }

    /**
     * {@inheritdoc}
     */
    public function submit($data): bool
    {
        return $this->clicked = isset($data[$this->name]) && (string) $data[$this->name] === $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function view(?HttpFieldPath $parent = null): ButtonViewInterface
    {
        return new ButtonView($parent ? $parent->add($this->name)->get() : $this->name, $this->value, $this->clicked());
    }
}
