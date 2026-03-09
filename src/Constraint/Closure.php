<?php

namespace Bdf\Form\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

use function is_array;
use function is_callable;
use function sprintf;
use function trigger_error;

/**
 * Handle custom constraint using a callback
 *
 * <code>
 * // The callback may return a boolean : if it's false, use the 'message' parameter as error
 * new Closure(
 *     callback: function ($value, ElementInterface $element) {
 *         return $this->checkValue($value);
 *     },
 *     message: 'my error',
 * );
 *
 * // You can also return a string to define a custom message
 * new Closure(function ($value, ElementInterface $element) {
 *     if (!$this->checkValue($value)) {
 *         return 'my error';
 *     }
 * });
 *
 * // To define an error code, return the error as an array
 * new Closure(function ($value, ElementInterface $element) {
 *     if (!$this->checkValue($value)) {
 *         return ['message' => 'my error', 'code' => 'MY_ERROR'];
 *     }
 * });
 * </code>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Closure extends Constraint
{
    /**
     * @var string
     */
    public $message = 'The value is invalid';

    /**
     * @var callable(mixed,\Bdf\Form\ElementInterface,\Symfony\Component\Validator\Context\ExecutionContextInterface):(bool|string|array{code?: string, message?: string})
     */
    public $callback;

    public function __construct($callback, ?string $message = null)
    {
        if (!is_callable($callback) && is_array($callback)) {
            @trigger_error(sprintf('Passing an array of options to %s is deprecated since 1.7. Pass the callback as first parameter and the message as second parameter instead.', self::class), E_USER_DEPRECATED);

            $options = $callback;
            $callback = $options['callback'] ?? null;
            $message ??= $options['message'] ?? null;
        }

        parent::__construct($options ?? null);

        $this->callback = $callback;

        if ($message !== null) {
            $this->message = $message;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
    {
        return 'callback';
    }
}
