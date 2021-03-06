<?php

namespace Bdf\Form\Error;

use Bdf\Form\Child\Http\HttpFieldPath;

/**
 * Print and format form errors
 * Printer are single use objects
 *
 * Note: Element's errors are set before children ones
 */
interface FormErrorPrinterInterface
{
    /**
     * Print the "global" error
     *
     * @param string $error
     *
     * @see FormError::global()
     */
    public function global(string $error): void;

    /**
     * Print the error code
     *
     * @param string $code
     *
     * @see FormError::code()
     */
    public function code(string $code): void;

    /**
     * Print the HTTP field
     *
     * @param HttpFieldPath $field
     *
     * @see FormError::field()
     */
    public function field(HttpFieldPath $field): void;

    /**
     * Print a child error
     *
     * @param string $name The child name
     * @param FormError $error The child error
     *
     * @see FormError::children()
     */
    public function child(string $name, FormError $error): void;

    /**
     * Print / finalize the printer
     * After this call, the printer should not be reused
     *
     * @return mixed
     */
    public function print();
}
