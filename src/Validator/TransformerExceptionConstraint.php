<?php

namespace Bdf\Form\Validator;

use Exception;
use Symfony\Component\Validator\Constraint;

/**
 * @internal
 */
final class TransformerExceptionConstraint extends Constraint
{
    const TRANSFORM_ERROR = 'b5acab45-80b0-4808-8784-6577e37ac869';

    /** @var array<string, string> */
    protected static $errorNames = [
        self::TRANSFORM_ERROR => 'TRANSFORM_ERROR',
    ];

    /**
     * The error message. If null, the exception's message will be taken
     *
     * @var string|null
     */
    public $message = null;

    /**
     * The error code
     *
     * @var string
     */
    public $code = self::TRANSFORM_ERROR;

    /**
     * The transformer exception
     *
     * @var Exception
     */
    public $exception;

    /**
     * Use to validate the exception
     * If the closure returns false, the transformer error will be ignored
     *
     * Note: To change the message or code corresponding to the exception, set code or message attributes on the constraint
     *
     * @var callable(mixed,TransformerExceptionConstraint,\Bdf\Form\ElementInterface):bool|null
     */
    public $validationCallback;

    /**
     * Does the transformation error should be ignored ?
     * If true, the transformation exception will be ignored
     *
     * @var bool
     */
    public $ignoreException = false;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'exception';
    }

    /**
     * Set the exception on the constraint
     *
     * @param Exception $exception
     * @return static
     */
    public function withException(Exception $exception): self
    {
        $constraint = clone $this;
        $constraint->exception = $exception;

        return $constraint;
    }
}
