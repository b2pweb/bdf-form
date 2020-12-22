<?php

namespace Bdf\Form\Phone;

use Bdf\Validator\ValidatorBuilder;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;

class ValidPhoneNumberTest extends TestCase
{
    /**
     *
     */
    public function test()
    {
        $constraint = new ValidPhoneNumber();

        $validator = (new ValidatorBuilder())->getValidator();
        $this->assertTrue($validator->validate('not phone number', $constraint)->isEmpty());
        $this->assertTrue($validator->validate(PhoneNumberUtil::getInstance()->parse('0123456789', 'FR'), $constraint)->isEmpty());

        $errors = $validator->validate(PhoneNumberUtil::getInstance()->parse('1234', 'FR'), $constraint);
        $this->assertFalse($errors->isEmpty());
        $this->assertEquals('The phone number is not valid.', $errors->get(0)->getMessage());
        $this->assertEquals('5169f03c-ec96-4e62-8651-9ee6766e0b5a', $errors->get(0)->getCode());
        $this->assertEquals(PhoneNumberUtil::getInstance()->parse('1234', 'FR'), $errors->get(0)->getInvalidValue());
    }
}
