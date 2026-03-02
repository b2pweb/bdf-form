<?php

namespace Bdf\Form\Phone;

use Symfony\Component\Validator\Constraint;

use function is_array;
use function sprintf;
use function trigger_error;

/**
 * Check if the phone number is valid
 */
class ValidPhoneNumber extends Constraint
{
    const INVALID_PHONE_NUMBER_ERROR = '5169f03c-ec96-4e62-8651-9ee6766e0b5a';

    protected const ERROR_NAMES = [self::INVALID_PHONE_NUMBER_ERROR => 'INVALID_PHONE_NUMBER_ERROR'];
    protected static $errorNames = self::ERROR_NAMES;

    /**
     * The error message
     *
     * @var string
     */
    public $message = 'The phone number is not valid.';

    public function __construct($message = null)
    {
        if (is_array($message)) {
            @trigger_error(sprintf('Passing an array of options to the "%s" constraint is deprecated since version 1.7 and support for it will be removed in 2.0. Use named arguments instead.', __CLASS__), E_USER_DEPRECATED);

            $options = $message;
            $message = $options['message'] ?? null;
        }

        parent::__construct($options ?? null);

        $this->message = $message ?? $this->message;
    }
}
