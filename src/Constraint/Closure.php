<?php

namespace Bdf\Form\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Handle custom constraint using a callback
 *
 * <code>
 * // The callback may return a boolean : if it's false, use the 'message' parameter as error
 * new Closure([
 *     'callback' => function ($value, ElementInterface $element) {
 *         return $this->checkValue($value);
 *     },
 *     'message' => 'my error',
 * ]);
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

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(): ?string
    {
        return 'callback';
    }
}
