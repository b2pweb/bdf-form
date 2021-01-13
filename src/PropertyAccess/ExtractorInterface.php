<?php

namespace Bdf\Form\PropertyAccess;

/**
 * ExtractorInterface
 * 
 * @package Bdf\Form\PropertyAccess
 */
interface ExtractorInterface extends AccessorInterface
{
    const EXTRACTION = 'extraction';
    
    /**
     * Extract the target property value
     * 
     * @param array|object $source
     * 
     * @return void
     */
    public function extract($source)/*: void*/;
}
