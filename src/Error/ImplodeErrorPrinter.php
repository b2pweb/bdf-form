<?php

namespace Bdf\Form\Error;

use Bdf\Form\Child\Http\HttpFieldPath;
use Override;

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
    private readonly string $separator;

    /**
     * Lines of errors
     *
     * @var string[]
     */
    private array $lines = [];

    /**
     * Does the printer is visiting a child ?
     * If true, call to print will do nothing
     *
     * @var bool
     */
    private bool $inChild = false;


    /**
     * ImplodeErrorsPrinter constructor.
     *
     * @param string $separator
     */
    public function __construct(string $separator = PHP_EOL)
    {
        $this->separator = $separator;
    }

    #[Override]
    public function field(HttpFieldPath $field): void
    {
        // Ignore field name
    }

    #[Override]
    public function global(string $error): void
    {
        $this->lines[] = $error;
    }

    #[Override]
    public function code(string $code): void
    {
        // Ignore code
    }

    #[Override]
    public function child(string $name, FormError $error): void
    {
        $this->inChild = true;
        $error->print($this);
        $this->inChild = false;
    }

    #[Override]
    public function print(): ?string
    {
        return $this->inChild ? null : implode($this->separator, $this->lines);
    }
}
