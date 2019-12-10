<?php

namespace Bdf\Form\Filter;

use Bdf\Form\ElementInterface;

/**
 * FilterInterface
 */
interface FilterInterface
{
    /**
     * Filter the input form element
     * 
     * @param mixed $value The HTTP value
     * @param ElementInterface $input
     * 
     * @return mixed Returns the filtered value
     */
    public function filter($value, $input);
}
