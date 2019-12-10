<?php

namespace Bdf\Form\Error;

/**
 * Format errors as a string
 */
final class StringErrorPrinter implements FormErrorPrinterInterface
{
    /**
     * @var string
     */
    private $lineSeparator = PHP_EOL;

    /**
     * @var string
     */
    private $indentString = '  ';

    /**
     * @var string
     */
    private $nameSeparator = ' : ';

    /**
     * @var int
     */
    private $maxDepth = PHP_INT_MAX;

    /**
     * @var integer
     */
    private $depth = 0;

    /**
     * @var string
     */
    private $output = '';

    /**
     * {@inheritdoc}
     */
    public function global(string $error): void
    {
        $this->output .= $error;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function print()
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
