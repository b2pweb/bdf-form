<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Child\ChildInterface;

/**
 * Adapt a simple callback to FilterInterface
 * Takes the value, the ChildInterface, and the default value as parameters
 *
 * <code>
 * $builder->filter(function ($value, ChildInterface $input, $default) {
 *     return $this->clean($value);
 * });
 * </code>
 *
 * @see ChildBuilderInterface::filter()
 */
final class ClosureFilter implements FilterInterface
{
    /**
     * @var callable
     */
    protected $callback;


    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($value, ChildInterface $input, $default)
    {
        return ($this->callback)($value, $input, $default);
    }
}
