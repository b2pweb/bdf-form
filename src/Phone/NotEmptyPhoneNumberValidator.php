<?php

namespace Bdf\Form\Phone;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlankValidator;

/**
 * NotBlank implementation for PhoneNumber value
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class NotEmptyPhoneNumberValidator extends NotBlankValidator
{
    private readonly PhoneNumberUtil $formatter;


    /**
     * PhoneNumberValidator constructor.
     * @param PhoneNumberUtil|null $formatter
     */
    public function __construct(?PhoneNumberUtil $formatter = null)
    {
        $this->formatter = $formatter ?? PhoneNumberUtil::getInstance();
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
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
