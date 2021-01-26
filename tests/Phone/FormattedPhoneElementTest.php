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
use libphonenumber\PhoneNumberFormat;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormattedPhoneElementTest
 */
class FormattedPhoneElementTest extends TestCase
{
    /**
     *
     */
    public function test_default()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertFalse($element->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_success()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertTrue($element->submit('+330142563698')->valid());
        $this->assertSame('+33142563698', $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_patch_with_value()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);
        $element->import('+33142563695');

        $this->assertTrue($element->patch('+33142563698')->valid());
        $this->assertSame('+33142563698', $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_patch_null()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);
        $element->import('+33142563695');

        $this->assertTrue($element->patch(null)->valid());
        $this->assertSame('+33142563695', $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     * @dataProvider provideFormats
     */
    public function test_formats($format, $formatted)
    {
        $element = new FormattedPhoneElement(new PhoneElement(null, null, function () { return 'FR'; }), $format);

        $this->assertTrue($element->submit('0142563698')->valid());
        $this->assertSame($formatted, $element->value());
        $this->assertSame('0142563698', $element->httpValue());
        $this->assertTrue($element->error()->empty());
    }

    public function provideFormats()
    {
        return [
            [PhoneNumberFormat::E164, '+33142563698'],
            [PhoneNumberFormat::INTERNATIONAL, '+33 1 42 56 36 98'],
            [PhoneNumberFormat::NATIONAL, '01 42 56 36 98'],
            [PhoneNumberFormat::RFC3966, 'tel:+33-1-42-56-36-98'],
        ];
    }

    /**
     *
     */
    public function test_submit_malformed_phone()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertTrue($element->submit('invalid')->valid());
        $this->assertSame('invalid', $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_null()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertTrue($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_constraint()
    {
        $element = new FormattedPhoneElement(new PhoneElement(new ConstraintValueValidator([new NotBlank()])), PhoneNumberFormat::E164);

        $this->assertFalse($element->submit(null)->valid());
        $this->assertNull($element->value());
        $this->assertEquals('This value should not be blank.', $element->error()->global());

        $this->assertTrue($element->submit('+330142563698')->valid());
        $this->assertSame('+33142563698', $element->value());
        $this->assertTrue($element->error()->empty());
    }

    /**
     *
     */
    public function test_submit_with_transformer_exception()
    {
        $transformer = $this->createMock(TransformerInterface::class);
        $transformer->expects($this->once())->method('transformFromHttp')->willThrowException(new TransformationFailedException('my error'));
        $element = new FormattedPhoneElement(new PhoneElement(null, $transformer), PhoneNumberFormat::E164);

        $this->assertFalse($element->submit('aa')->valid());
        $this->assertSame('aa', $element->value());
        $this->assertEquals('my error', $element->error()->global());
    }

    /**
     *
     */
    public function test_transformer()
    {
        $element = new FormattedPhoneElement(new PhoneElement(null, new ClosureTransformer(function ($value, $_, $toPhp) {
            return $toPhp ? base64_decode($value) : base64_encode($value);
        })), PhoneNumberFormat::E164);

        $element->submit(base64_encode('+330142563698'));
        $this->assertEquals('+33142563698', $element->value());
        $this->assertSame(base64_encode('+330142563698'), $element->httpValue());
    }

    /**
     *
     */
    public function test_import()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertSame('+33142563698', $element->import('+330142563698')->value());
        $this->assertSame('+330142563698', $element->import('+330142563698')->httpValue());
    }

    /**
     *
     */
    public function test_httpValue_null()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertNull($element->import(null)->httpValue());
    }

    /**
     *
     */
    public function test_container()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

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
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertInstanceOf(LeafRootElement::class, $element->root());
    }

    /**
     *
     */
    public function test_root_with_container()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

        $this->assertNull($element->container());

        $container = new Child('name', $element);
        $container->setParent(new Form(new ChildrenCollection()));

        $element = $element->setContainer($container);

        $this->assertSame($container->parent()->root(), $element->root());
    }

    /**
     *
     */
    public function test_view()
    {
        $element = new FormattedPhoneElement(new PhoneElement(), PhoneNumberFormat::E164);

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
