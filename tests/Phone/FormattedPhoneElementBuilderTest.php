<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Child\Child;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormattedPhoneElementBuilderTest
 */
class FormattedPhoneElementBuilderTest extends TestCase
{
    /**
     * @var FormattedPhoneElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new FormattedPhoneElementBuilder(new PhoneElementBuilder());
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(FormattedPhoneElement::class, $element);
    }

    /**
     *
     */
    public function test_format()
    {
        $element = $this->builder
            ->format(PhoneNumberFormat::INTERNATIONAL)
            ->region('FR')
            ->buildElement()
        ;

        $this->assertSame('+33 2 32 85 47 98', $element->submit('0232854798')->value());
    }

    /**
     *
     */
    public function test_must_validate_number_by_default()
    {
        $element = $this->builder->buildElement();
        $element->submit('1');

        $this->assertFalse($element->valid());
        $this->assertEquals('The phone number is not valid.', $element->error()->global());
        $this->assertEquals('INVALID_PHONE_NUMBER_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_allowInvalidNumber()
    {
        $element = $this->builder->allowInvalidNumber()->buildElement();
        $element->submit('1');

        $this->assertTrue($element->valid());
        $this->assertNull($element->error()->global());
        $this->assertSame('1', $element->httpValue());
    }

    /**
     *
     */
    public function test_validateNumber()
    {
        $element = $this->builder->validateNumber('my error')->buildElement();
        $element->submit('1');

        $this->assertFalse($element->valid());
        $this->assertEquals('my error', $element->error()->global());
        $this->assertEquals('INVALID_PHONE_NUMBER_ERROR', $element->error()->code());
    }

    /**
     *
     */
    public function test_satisfy()
    {
        $element = $this->builder->satisfy(new NotBlank())->buildElement();

        $this->assertFalse($element->submit(null)->valid());
        $this->assertTrue($element->submit('+33452879613')->valid());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = $this->builder->transformer(function ($value, $element, $toPhp) {
            return $toPhp ? base64_decode($value) : base64_encode($value);
        })->buildElement();

        $this->assertEquals('+33452879613', $element->submit(base64_encode('+33452879613'))->value());
        $this->assertSame(base64_encode('+33452879613'), $element->httpValue());
    }

    /**
     *
     */
    public function test_value()
    {
        $element = $this->builder->value('0452123698')->buildElement();

        $this->assertSame('0452123698', $element->value());
    }

    /**
     *
     */
    public function test_required()
    {
        $element = $this->builder->required()->buildElement();

        $element->submit(null);
        $this->assertEquals('This value should not be blank.', $element->error()->global());
    }

    /**
     *
     */
    public function test_required_with_custom_message()
    {
        $element = $this->builder->required('my message')->buildElement();

        $element->submit(null);
        $this->assertEquals('my message', $element->error()->global());
    }

    /**
     *
     */
    public function test_region()
    {
        $element = $this->builder->region('FR')->buildElement();

        $this->assertEquals('+33452879613', $element->submit('0452879613')->value());
    }

    /**
     *
     */
    public function test_regionResolver()
    {
        $element = $this->builder->regionResolver(function () { return 'FR'; })->buildElement();

        $this->assertEquals('+33452879613', $element->submit('0452879613')->value());
    }

    /**
     *
     */
    public function test_regionInput()
    {
        $element = $this->builder->regionInput('country')->buildElement();

        $formBuilder = new FormBuilder();
        $formBuilder->string('country');
        $form = $formBuilder->buildElement();

        $element = $element->setContainer(new Child('phone', $element));
        $element->container()->setParent($form);

        $form['country']->element()->import('fr');
        $this->assertEquals('+33452879613', $element->submit('0452879613')->value());

        $form['country']->element()->import('gb');
        $this->assertEquals('+44452879613', $element->submit('0452879613')->value());
    }
}
