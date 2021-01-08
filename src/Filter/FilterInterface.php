<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Child\ChildInterface;

/**
 * Filter the input value
 * The filter is the first applied transformation of the HTTP value
 *
 * Unlike transformers, filters are only applied in transformation from HTTP to PHP
 * And filters can only be set on child element
 */
interface FilterInterface
{
    /**
     * Filter the input form element
     * 
     * @param mixed $value The HTTP value
     * @param ChildInterface $input
     * 
     * @return mixed Returns the filtered value
     */
    public function filter($value, ChildInterface $input);
}
