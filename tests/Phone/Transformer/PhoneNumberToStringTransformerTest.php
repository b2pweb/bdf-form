<?php

namespace Bdf\Form\Phone\Transformer;

use Bdf\Form\Aggregate\FormBuilder;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;

class PhoneNumberToStringTransformerTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $builder = new FormBuilder();
        $builder->phone('foo')->modelTransformer(new PhoneNumberToStringTransformer())->getter()->setter();

        $form = $builder->buildElement();

        $form->import(['foo' => '0236547841']);
        $this->assertSame(['foo' => '0236547841'], $form->value());

        $this->assertInstanceOf(PhoneNumber::class, $form['foo']->element()->value());
    }

    /**
     *
     */
    public function test_with_format_and_formatter()
    {
        $builder = new FormBuilder();
        $builder->phone('foo')
            ->modelTransformer(new PhoneNumberToStringTransformer(PhoneNumberFormat::NATIONAL, PhoneNumberUtil::getInstance()))
            ->getter()->setter()
            ->region('FR')
        ;

        $form = $builder->buildElement();

        $form->submit(['foo' => '0236547841']);
        $this->assertSame(['foo' => '02 36 54 78 41'], $form->value());

        $this->assertInstanceOf(PhoneNumber::class, $form['foo']->element()->value());

        $form->import(['foo' => '02 36 54 78 41']);
        $this->assertInstanceOf(PhoneNumber::class, $form['foo']->element()->value());
        $this->assertEquals('236547841', $form['foo']->element()->value()->getNationalNumber());
    }

    /**
     *
     */
    public function test_with_invalid_number_should_return_unformated_value()
    {
        $builder = new FormBuilder();
        $builder->phone('foo')->modelTransformer(new PhoneNumberToStringTransformer())->getter()->setter();

        $form = $builder->buildElement();

        $form->submit(['foo' => 'invalid']);
        $this->assertSame(['foo' => 'invalid'], $form->value());

        $this->assertInstanceOf(PhoneNumber::class, $form['foo']->element()->value());
    }
    /**
     *
     */
    public function test_null()
    {
        $builder = new FormBuilder();
        $builder->phone('foo')->modelTransformer(new PhoneNumberToStringTransformer())->getter()->setter();

        $form = $builder->buildElement();

        $form->import(['foo' => null]);
        $this->assertSame(['foo' => null], $form->value());

        $this->assertNull($form['foo']->element()->value());
    }
}
