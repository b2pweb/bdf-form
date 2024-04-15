<?php

namespace Bdf\Form\Phone;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\Http\HttpFieldPath;
use Bdf\Form\Csrf\CsrfElement;
use Bdf\Form\Leaf\LeafRootElement;
use Bdf\Form\Transformer\ClosureTransformer;
use Bdf\Form\Transformer\TransformerInterface;
use Bdf\Form\Validator\ConstraintValueValidator;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class PhoneElementTest
 */
class PhoneElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new PhoneElement();

        $this->assertFalse($element->valid());
        $this->assertTrue($element->failed());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new PhoneElement();

        $this->assertTrue($element->submit('+330142563698')->valid());
        $this->assertFalse($element->failed());
        $this->assertInstanceOf(PhoneNumber::class, $element->value());
        $this->assertEquals(33, $element->value()->getCountryCode());
        $this->assertEquals('142563698', $element->value()->getNationalNumber());
        $this->assertEquals('+330142563698', $element->value()->getRawInput());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_malformed_phone()
    {
        $element = new PhoneElement();

        $this->assertTrue($element->submit('invalid')->valid());
        $this->assertInstanceOf(PhoneNumber::class, $element->value());
        $this->assertFalse($element->value()->hasCountryCode());
        $this->assertFalse($element->value()->hasNationalNumber());
        $this->assertEquals('invalid', $element->value()->getRawInput());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new PhoneElement();

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertNull($element->httpValue());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_empty_string()
    {
        $element = new PhoneElement();

        $this->assertTrue($element->submit('')->valid());
        $this->assertSame('', $element->value()->getRawInput());
        $this->assertSame('', $element->httpValue());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new PhoneElement(new ConstraintValueValidator([new NotBlank()]));

        $this->assertFalse($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertEquals('This value should not be blank.', $element->error()->global());

        $this->assertTrue($element->submit('+330142563698')->valid());
        $this->assertInstanceOf(PhoneNumber::class, $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new PhoneElement(null, $transformer);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new PhoneElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? base64_decode($value) : base64_encode($value);
        }));

        $element->submit(base64_encode('+330142563698'));
        $this->assertInstanceOf(PhoneNumber::class, $element->value());
        $this->assertEquals(33, $element->value()->getCountryCode());
        $this->assertEquals('142563698', $element->value()->getNationalNumber());
        $this->assertEquals('+330142563698', $element->value()->getRawInput());
        $this->assertSame(base64_encode('+330142563698'), $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new PhoneElement();

        $phone = new PhoneNumber();
        $this->assertSame($phone, $element->import($phone)->value());
    }

    /**
     *
     */
    public function test_import_null()
    {
        $element = new PhoneElement();

        $this->assertNull($element->import(null)->value());
    }

    /**
     * @dataProvider provideInvalidValue
     */
    public function test_import_invalid_values($value)
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('The import()\'ed value of a Bdf\Form\Phone\PhoneElement must be an instance of libphonenumber\PhoneNumber or null');
        $element = new PhoneElement();

        $element->import($value);
    }

    /**
     *
     */
    public function provideInvalidValue()
    {
        return [
            [[]],
            [new \stdClass()],
            [STDIN],
            [''],
            ['foo'],
            [true],
            [false],
            [1],
            [1.2],
        ];
    }

    /**
     *
     */
    public function test_httpValue_with_raw_input()
    {
        $element = new PhoneElement();

        $phone = new PhoneNumber();
        $phone->setRawInput('+330142563698');
        $this->assertSame('+330142563698', $element->import($phone)->httpValue());
    }

    /**
     *
     */
    public function test_httpValue_without_raw_input()
    {
        $element = new PhoneElement();

        $phone = new PhoneNumber();
        $phone
            ->setCountryCode(33)
            ->setNationalNumber('142563698')
        ;

        $this->assertSame('+33142563698', $element->import($phone)->httpValue());
    }

    /**
     *
     */
    public function test_httpValue_null()
    {
        $element = new PhoneElement();

        $this->assertNull($element->import(null)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new PhoneElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $newElement = $element->setContainer($container);

        $this->assertNotSame($element, $newElement);
        $this->assertSame($container, $newElement->container());
    }

    /**
     *
     */
    public function test_root_without_container()
    {
        $element = new PhoneElement();

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new PhoneElement();

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent($form = new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }

    /**
     *
     */
    public function test_view()
    {
        $element = new PhoneElement();

        $view = $element->view(HttpFieldPath::named('tel'));

        $this->assertEquals(PhoneElement::class, $view->type());
        $this->assertEquals('<input type="tel" name="tel" value="" />', (string) $view);
        $this->assertEquals('tel', $view->name());
        $this->assertFalse($view->hasError());

        $element->submit('0123456789');

        $view = $element->view(HttpFieldPath::named('tel'));

        $this->assertEquals(PhoneElement::class, $view->type());
        $this->assertEquals('<input type="tel" name="tel" value="0123456789" />', (string) $view);
        $this->assertEquals('tel', $view->name());
        $this->assertFalse($view->hasError());
    }
}
