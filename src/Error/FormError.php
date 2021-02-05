<?php

namespace Bdf\Form\Error;

use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\ElementInterface;
use InvalidArgumentException;
use Stringable;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Store errors of a form element
 *
 * @see ElementInterface::error()
 * @see ChildInterface::error()
 */
final class FormError implements Stringable
{
    /**
     * @var FormError|null
     */
    private static $null;

    /**
     * @var HttpFieldPath|null
     */
    private $field;

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
     * Get the HTTP field name of the current element
     *
     * @return HttpFieldPath|null The field path, or null is it's on the root element
     */
    public function field(): ?HttpFieldPath
    {
        return $this->field;
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
        if ($this->field) {
            $printer->field($this->field);
        }

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
     * Add the HTTP field on the form error
     * The field will be applied to all children as prefix
     *
     * @param HttpFieldPath $field The current element field
     *
     * @return FormError The new FormError instance
     */
    public function withField(HttpFieldPath $field): FormError
    {
        $error = clone $this;

        $error->field = $field;
        $error->children = [];

        foreach ($this->children as $name => $child) {
            $error->children[$name] = $child->withPrefixField($field);
        }

        return $error;
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

    /**
     * Add recursively a prefix field on children
     *
     * @param HttpFieldPath $prefix Prefix to add
     *
     * @return FormError
     */
    private function withPrefixField(HttpFieldPath $prefix)
    {
        $error = clone $this;

        $error->field = $this->field ? $prefix->concat($this->field) : $prefix;
        $error->children = [];

        foreach ($this->children as $name => $child) {
            $error->children[$name] = $child->withPrefixField($prefix);
        }

        return $error;
    }
}
