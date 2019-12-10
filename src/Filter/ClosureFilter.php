<?php

namespace Bdf\Form\Filter;

/**
 * ClosureFilter
 * 
 * @package Bdf\Form\Filter
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
    public function filter($value, $input)
    {
        return ($this->callback)($value, $input);
    }
}
