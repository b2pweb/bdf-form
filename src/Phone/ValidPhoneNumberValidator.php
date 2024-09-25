<?php

namespace Bdf\Form\Phone;

use libphonenumber\PhoneNumber as PhoneNumberValue;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for @see ValidPhoneNumber
 */
class ValidPhoneNumberValidator extends ConstraintValidator
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
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPhoneNumber) {
            throw new UnexpectedTypeException($constraint, ValidPhoneNumber::class);
        }

        if (!$value instanceof PhoneNumberValue) {
            return;
        }

        if (!$this->formatter->isValidNumber($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(ValidPhoneNumber::INVALID_PHONE_NUMBER_ERROR)
                ->addViolation()
            ;
        }
    }
}
