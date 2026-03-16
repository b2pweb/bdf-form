<?php

namespace Bdf\Form\PropertyAccess;

/**
 * Extract value from entity or array
 */
interface ExtractorInterface extends AccessorInterface
{
    public const string EXTRACTION = 'extraction';

    /**
     * Extract the target property value
     *
     * @param array|object $source
     *
     * @return mixed The extracted value
     */
    public function extract(array|object $source)/*: mixed*/;
}
