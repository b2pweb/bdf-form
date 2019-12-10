<?php

namespace Bdf\Form\Error;

/**
 * Print and format form errors
 * Printer are single use objects
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
