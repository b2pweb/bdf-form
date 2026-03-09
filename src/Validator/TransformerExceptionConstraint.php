<?php

namespace Bdf\Form\Validator;

use Exception;
use Symfony\Component\Validator\Constraint;

use function is_array;
use function sprintf;
use function trigger_error;

/**
 * @internal
 */
final class TransformerExceptionConstraint extends Constraint
{
    const TRANSFORM_ERROR = 'b5acab45-80b0-4808-8784-6577e37ac869';

    protected const ERROR_NAMES = [self::TRANSFORM_ERROR => 'TRANSFORM_ERROR'];
    protected static $errorNames = self::ERROR_NAMES;

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

    public function __construct($exception = null, ?string $message = null, ?string $code = null, ?callable $validationCallback = null, ?bool $ignoreException = null)
    {
        if (is_array($exception)) {
            @trigger_error(sprintf('Passing an array of options to %s is deprecated since 1.7 and will not be supported in 2.0. Use named parameters instead.', __METHOD__), E_USER_DEPRECATED);

            $options = $exception;
        }

        parent::__construct($options ?? null);

        $this->exception = $exception ?? $this->exception;
        $this->message = $message ?? $this->message;
        $this->code = $code ?? $this->code;
        $this->validationCallback = $validationCallback ?? $this->validationCallback;
        $this->ignoreException = $ignoreException ?? $this->ignoreException;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
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
