<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Error\FormError;
use Bdf\Form\Validator\ConstraintValueValidator;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;

class NotEmptyPhoneNumberTest extends TestCase
{
    /**
     *
     */
    public function test_empty()
    {
        $validator = new ConstraintValueValidator([new NotEmptyPhoneNumber()]);
        $input = new PhoneElement();

        $this->assertEquals(FormError::message('This value should not be blank.', 'IS_BLANK_ERROR'), $validator->validate(null, $input));
        $this->assertEquals(FormError::message('This value should not be blank.', 'IS_BLANK_ERROR'), $validator->validate('', $input));
        $this->assertEquals(FormError::message('This value should not be blank.', 'IS_BLANK_ERROR'), $validator->validate(new PhoneNumber(), $input));
        $this->assertEquals(FormError::message('This value should not be blank.', 'IS_BLANK_ERROR'), $validator->validate((new PhoneNumber())->setRawInput(''), $input));
    }

    /**
     *
     */
    public function test_not_empty()
    {
        $validator = new ConstraintValueValidator([new NotEmptyPhoneNumber()]);
        $input = new PhoneElement();

        $this->assertEquals(FormError::null(), $validator->validate((new PhoneNumber())->setRawInput('invalid'), $input));
        $this->assertEquals(FormError::null(), $validator->validate((new PhoneNumber())->setNationalNumber('123456'), $input));
    }

    /**
     *
     */
    public function test_custom_message()
    {
        $validator = new ConstraintValueValidator([new NotEmptyPhoneNumber(['message' => 'my error'])]);
        $input = new PhoneElement();

        $this->assertEquals(FormError::message('my error', 'IS_BLANK_ERROR'), $validator->validate(null, $input));
    }
}
