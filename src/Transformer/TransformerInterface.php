<?php

namespace Bdf\Form\Transformer;

use Bdf\Form\ElementInterface;

/**
 * Transform form elements data
 */
interface TransformerInterface
{
    /**
     * Model to http transformation
     * 
     * @param mixed $value The value to transform
     * @param ElementInterface $input The form element
     * 
     * @return mixed The view value
     */
    public function transformToHttp($value, ElementInterface $input);

    /**
     * Http to model transformation
     * 
     * @param mixed $value The value to transform
     * @param ElementInterface $input The form element
     * 
     * @return mixed The model value
     */
    public function transformFromHttp($value, ElementInterface $input);
}
