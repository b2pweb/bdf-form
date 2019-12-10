<?php

namespace Bdf\Form\Child\Http;

/**
 * Handle the HTTP fields for a child
 *
 * Note: The implementation may handle other types than array of HTTP fields (like JSON string)
 */
interface HttpFieldsInterface
{
    /**
     * Extract the required HTTP fields from raw HTTP fields
     *
     * @param mixed $httpFields The raw HTTP fields
     * @param mixed $defaultValue Default value to return when the input fields are missing
     *
     * @return mixed
     */
    public function extract($httpFields, $defaultValue);

    /**
     * Format an element value to HTTP fields
     *
     * @param mixed $value Value to format
     *
     * @return mixed
     */
    public function format($value);
}
