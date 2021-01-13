<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Child\Child;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class PhoneElementBuilderTest
 */
class PhoneElementBuilderTest extends TestCase
{
    /**
     * @var PhoneElementBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new PhoneElementBuilder();
    }

    /**
     *
     */
    public function test_buildElement()
    {
        $element = $this->builder->buildElement();

        $this->assertInstanceOf(PhoneElement::class, $element);
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
    public function test_formatter()
    {
        $element = $this->builder->formatter($formatter = $this->createMock(PhoneNumberUtil::class))->buildElement();

        $this->assertInstanceOf(PhoneElement::class, $element);
        $r = (new \ReflectionClass($element))->getProperty('formatter');
        $r->setAccessible(true);

        $this->assertSame($formatter, $r->getValue($element));
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

        $this->assertEquals('+33452879613', $element->submit(base64_encode('+33452879613'))->value()->getRawInput());
        $this->assertSame(base64_encode('+33452879613'), $element->httpValue());
    }

    /**
     *
     */
    public function test_value()
    {
        $phone = new PhoneNumber();
        $element = $this->builder->value($phone)->buildElement();

        $this->assertSame($phone, $element->value());
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

        $this->assertEquals(33, $element->submit('0452879613')->value()->getCountryCode());
    }

    /**
     *
     */
    public function test_regionResolver()
    {
        $element = $this->builder->regionResolver(function () { return 'FR'; })->buildElement();

        $this->assertEquals(33, $element->submit('0452879613')->value()->getCountryCode());
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
        $this->assertEquals(33, $element->submit('0452879613')->value()->getCountryCode());

        $form['country']->element()->import('gb');
        $this->assertEquals(44, $element->submit('0452879613')->value()->getCountryCode());
    }
}
