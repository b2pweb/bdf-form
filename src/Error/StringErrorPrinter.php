<?php

namespace Bdf\Form\Error;

use Bdf\Form\Child\Http\HttpFieldPath;
use Override;

/**
 * Format errors as a string
 */
final class StringErrorPrinter implements FormErrorPrinterInterface
{
    private string $lineSeparator = PHP_EOL;
    private string $indentString = '  ';
    private string $nameSeparator = ' : ';
    private int $maxDepth = PHP_INT_MAX;
    private int $depth = 0;
    private string $output = '';

    #[Override]
    public function field(HttpFieldPath $field): void
    {
        // Ignore the field name
    }

    #[Override]
    public function global(string $error): void
    {
        $this->output .= $error;
    }

    #[Override]
    public function code(string $code): void
    {
        // Ignore code
    }

    #[Override]
    public function child(string $name, FormError $error): void
    {
        if ($this->maxDepth <= $this->depth) {
            return;
        }

        if (!empty($this->output)) {
            $this->output .= $this->lineSeparator;
        }

        $this->output .= str_repeat($this->indentString, $this->depth).$name.$this->nameSeparator;

        ++$this->depth;
        $error->print($this);
        --$this->depth;
    }

    #[Override]
    public function print(): string
    {
        return $this->output;
    }

    /**
     * Define the end of line string for separation children
     *
     * @param string $lineSeparator
     *
     * @return StringErrorPrinter
     */
    public function lineSeparator(string $lineSeparator): StringErrorPrinter
    {
        $this->lineSeparator = $lineSeparator;

        return $this;
    }

    /**
     * Define the indentation string
     *
     * @param string $indentString
     *
     * @return StringErrorPrinter
     */
    public function indentString(string $indentString): StringErrorPrinter
    {
        $this->indentString = $indentString;

        return $this;
    }

    /**
     * Define the separator between the child name and its error
     *
     * @param string $nameSeparator
     *
     * @return StringErrorPrinter
     */
    public function nameSeparator(string $nameSeparator): StringErrorPrinter
    {
        $this->nameSeparator = $nameSeparator;

        return $this;
    }

    /**
     * Define the max depth
     *
     * @param int $maxDepth
     *
     * @return StringErrorPrinter
     */
    public function maxDepth(int $maxDepth): StringErrorPrinter
    {
        $this->maxDepth = $maxDepth;

        return $this;
    }
}
