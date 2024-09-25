<?php

namespace Bdf\Form\Phone;

use Symfony\Component\Validator\Constraint;

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
}
