<?php

namespace Bdf\Form\Error;

use Bdf\Form\Child\Http\HttpFieldPath;

/**
 * Implode all errors into a string
 * The printer will visit recursively all children
 */
final class ImplodeErrorPrinter implements FormErrorPrinterInterface
{
    /**
     * The errors separator
     *
     * @var string
     */
    private $separator;

    /**
     * Lines of errors
     *
     * @var string[]
     */
    private $lines = [];

    /**
     * Does the printer is visiting a child ?
     * If true, call to print will do nothing
     *
     * @var bool
     */
    private $inChild = false;


    /**
     * ImplodeErrorsPrinter constructor.
     *
     * @param string $separator
     */
    public function __construct(string $separator = PHP_EOL)
    {
        $this->separator = $separator;
    }

    /**
     * {@inheritdoc}
     */
    public function field(HttpFieldPath $field): void
    {
        // Ignore field name
    }

    /**
     * {@inheritdoc}
     */
    public function global(string $error): void
    {
        $this->lines[] = $error;
    }

    /**
     * {@inheritdoc}
     */
    public function code(string $code): void
    {
        // Ignore code
    }

    /**
     * {@inheritdoc}
     */
    public function child(string $name, FormError $error): void
    {
        $this->inChild = true;
        $error->print($this);
        $this->inChild = false;
    }

    /**
     * {@inheritdoc}
     */
    public function print()
    {
        return $this->inChild ? null : implode($this->separator, $this->lines);
    }
}
