<?php

namespace Bdf\Form\Phone;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlankValidator;

/**
 * NotBlank implementation for PhoneNumber value
 */
class NotEmptyPhoneNumberValidator extends NotBlankValidator
{
    /**
     * @var PhoneNumberUtil
     */
    private $formatter;


    /**
     * PhoneNumberValidator constructor.
     * @param PhoneNumberUtil|null $formatter
     */
    public function __construct(?PhoneNumberUtil $formatter = null)
    {
        $this->formatter = $formatter ?? PhoneNumberUtil::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof PhoneNumber) {
            if ($value->hasRawInput()) {
                $value = $value->getRawInput();
            } else {
                $value = $value->getNationalNumber();
            }
        }

        parent::validate($value, $constraint);
    }
}
