<?php

namespace Bdf\Form\Error;

use Bdf\Form\Child\ChildInterface;
use Bdf\Form\ElementInterface;
use InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Store errors of a form element
 *
 * @see ElementInterface::error()
 * @see ChildInterface::error()
 */
final class FormError
{
    /**
     * @var FormError|null
     */
    private static $null;

    /**
     * @var string|null
     */
    private $global;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var FormError[]
     */
    private $children;


    /**
     * FormError constructor.
     * Prefer use static methods to instantiate the FormError
     *
     * @param string|null $global
     * @param string|null $code
     * @param FormError[] $children
     */
    public function __construct(?string $global, ?string $code, array $children)
    {
        $this->global = $global;
        $this->code = $code;
        $this->children = $children;
    }

    /**
     * Get the error of the current element, or the global error on an aggregate element
     *
     * @return string|null The error message, or null if there is no errors
     */
    public function global(): ?string
    {
        return $this->global;
    }

    /**
     * The error code of the current element
     *
     * @return string|null The error code, or null if not provided
     */
    public function code(): ?string
    {
        return $this->code;
    }

    /**
     * Get the children's errors
     * The errors are indexed by the child's name
     * Contains only non-empty errors
     *
     * @return FormError[]
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * Check if the error object is an empty one
     *
     * If true, there is no errors, neither on current elements, nor on children
     * An element returning an empty error is a valid one
     *
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->global) && empty($this->code) && empty($this->children);
    }

    /**
     * Export the errors into an array
     *
     * - The errors are indexed by the children's name
     * - If a child contains a "global" error, the error value will be the global error
     * - Else, call toArray() on the child's error
     * - If the current element contains a global error, return it at the int(0) index
     *
     * @return array
     */
    public function toArray(): array
    {
        $errors = [];

        if ($this->global) {
            $errors[0] = $this->global;
        }

        foreach ($this->children as $name => $child) {
            if ($child->global) {
                $errors[$name] = $child->global;
            } else {
                $errors[$name] = $child->toArray();
            }
        }

        return $errors;
    }

    /**
     * Format the error using the given printer
     *
     * @param FormErrorPrinterInterface $printer
     *
     * @return mixed The printer result
     */
    public function print(FormErrorPrinterInterface $printer)
    {
        if ($this->global) {
            $printer->global($this->global);
        }

        if ($this->code) {
            $printer->code($this->code);
        }

        foreach ($this->children as $name => $child) {
            $printer->child($name, $child);
        }

        return $printer->print();
    }

    /**
     * Format errors as string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->print(new StringErrorPrinter());
    }

    /**
     * Get an empty error instance
     *
     * @return FormError
     */
    public static function null(): FormError
    {
        if (self::$null) {
            return self::$null;
        }

        return self::$null = new FormError(null, null, []);
    }

    /**
     * Creates an error containing only the global message
     *
     * @param string $message The error message
     * @param string|null $code The error code
     *
     * @return FormError
     */
    public static function message(string $message, ?string $code = null): FormError
    {
        return new FormError($message, $code, []);
    }

    /**
     * Creates an aggregation of children errors
     *
     * @param FormError[] $errors The children errors, indexed by the child name
     *
     * @return FormError
     */
    public static function aggregate(array $errors): FormError
    {
        return new FormError(null, null, $errors);
    }

    /**
     * Create a form error from a symfony violation
     *
     * @param ConstraintViolationInterface $violation The violation instance
     *
     * @return FormError
     */
    public static function violation(ConstraintViolationInterface $violation): FormError
    {
        $message = (string) $violation->getMessage();
        $code = $violation->getCode();

        if ($code !== null && $violation instanceof ConstraintViolation && ($constraint = $violation->getConstraint()) !== null) {
            try {
                $code = $constraint->getErrorName($code);
            } catch (InvalidArgumentException $e) {
                // Ignore error
            }
        }

        return self::message($message, $code);
    }
}
