<?php

namespace Bdf\Form\Phone;

use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ValidatorBuilder;

class ValidPhoneNumberTest extends TestCase
{
    /**
     *
     */
    public function test()
    {
        $constraint = new ValidPhoneNumber();

        $validator = (new ValidatorBuilder())->getValidator();
        $this->assertCount(0, $validator->validate('not phone number', $constraint));
        $this->assertCount(0, $validator->validate(PhoneNumberUtil::getInstance()->parse('0123456789', 'FR'), $constraint));

        $errors = $validator->validate(PhoneNumberUtil::getInstance()->parse('1234', 'FR'), $constraint);
        $this->assertCount(1, $errors);
        $this->assertEquals('The phone number is not valid.', $errors->get(0)->getMessage());
        $this->assertEquals('5169f03c-ec96-4e62-8651-9ee6766e0b5a', $errors->get(0)->getCode());
        $this->assertEquals(PhoneNumberUtil::getInstance()->parse('1234', 'FR'), $errors->get(0)->getInvalidValue());
    }
}
